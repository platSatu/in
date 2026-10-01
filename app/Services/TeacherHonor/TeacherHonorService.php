<?php

namespace App\Services\TeacherHonor;

use App\Models\ClassSession;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Honor Pengajar (26 September 2026) -- satu-satunya tempat aturan hitung:
 *
 *   honor = jumlah kelas x fee Rupiah jenis kelasnya (Course Class)
 *
 * "1 kelas = 1 credit": setiap paket yang dibeli (course_package_purchases)
 * yang credit-nya terpotong di sesi bersama seorang pengajar dalam periode
 * itu dihitung SATU kelas -- berapa kali pun dipakai, berapa pun isinya.
 * Asal credit dibaca dari course_credit_allocations (dicatat
 * CourseCreditDebitService saat admin menyetujui sesi), jadi tidak ada
 * input tambahan. Waktu sesi = requested_at, sama dengan menu Jadwal.
 *
 * Periode terbuka: rekap dihitung langsung (fee mengikuti Course Class
 * terkini). Periode ditutup: rekap dikunci ke TeacherHonor.classes.
 *
 * Supaya tidak ada sesi yang terlewat (30 September 2026):
 * - periode per cabang harus menyambung tanpa tanggal bolong/bentrok
 *   (validateNewPeriod, nextPeriodDates untuk isian otomatis);
 * - periode baru bisa ditutup setelah tanggal tutupnya lewat DAN semua
 *   pengajuan kelas di rentangnya sudah disetujui/ditolak (closeBlockedReason).
 */
class TeacherHonorService
{
    /**
     * Rekap per pengajar untuk satu periode.
     *
     * @return Collection<int, array{teacher_user_id: string, teacher_name: string, class_count: int, honor_amount: float, classes: array}>
     */
    public function recap(TeacherHonorPeriod $period, ?string $teacherUserId = null): Collection
    {
        if (! $period->isOpen()) {
            return $period->honors()
                ->with('teacher')
                ->when($teacherUserId, fn ($query) => $query->where('teacher_user_id', $teacherUserId))
                ->get()
                ->map(fn (TeacherHonor $honor) => [
                    'teacher_user_id' => $honor->teacher_user_id,
                    'teacher_name' => $honor->teacher?->name ?? '-',
                    'class_count' => $honor->class_count,
                    'honor_amount' => (float) $honor->honor_amount,
                    'classes' => $honor->classes,
                    'honor' => $honor,
                ])
                ->sortBy('teacher_name')
                ->values();
        }

        $sessions = $this->sessionsIn($period)
            ->where('status', ClassSession::STATUS_APPROVED)
            ->whereNotNull('teacher_user_id') // potong credit manual tanpa pengajar = tanpa honor
            ->when($teacherUserId, fn ($query) => $query->where('teacher_user_id', $teacherUserId))
            ->with([
                'teacher',
                'student',
                'coursePackage.courseClass',
                'courseCredit.allocations.purchase.student',
                'courseCredit.allocations.purchase.coursePackage.courseClass',
            ])
            ->orderBy('requested_at')
            ->get();

        return $sessions
            ->groupBy('teacher_user_id')
            ->map(function (Collection $teacherSessions, string $teacherId) {
                $classes = [];

                foreach ($teacherSessions as $session) {
                    [$key, $source] = $this->creditSource($session);
                    $classes[$key] ??= $source + ['credit_total' => 0.0, 'sessions' => []];
                    $classes[$key]['credit_total'] += (float) $session->credit_amount_final;
                    $classes[$key]['sessions'][] = [
                        'at' => optional($session->requested_at)->format('Y-m-d H:i'),
                        'student' => $this->studentName($session->student),
                        'credit' => (float) $session->credit_amount_final,
                    ];
                }

                $classes = collect($classes)->sortBy(fn (array $class) => $class['class_name'].'|'.$class['owner'])->values()->all();

                return [
                    'teacher_user_id' => $teacherId,
                    'teacher_name' => $teacherSessions->first()->teacher?->name ?? '-',
                    'class_count' => count($classes),
                    'honor_amount' => (float) array_sum(array_column($classes, 'fee')),
                    'classes' => $classes,
                    'honor' => null,
                ];
            })
            ->sortBy('teacher_name')
            ->values();
    }

    /**
     * Tutup periode: rekap dikunci jadi 1 TeacherHonor per pengajar
     * (status pending), lalu periode tidak menghitung sesi baru lagi.
     */
    public function close(TeacherHonorPeriod $period, User $user): void
    {
        DB::transaction(function () use ($period, $user) {
            $period = TeacherHonorPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            if (! $period->isOpen()) {
                throw new InvalidTeacherHonorStateException('Periode ini sudah ditutup sebelumnya.');
            }

            if ($reason = $this->closeBlockedReason($period)) {
                throw new InvalidTeacherHonorStateException($reason);
            }

            foreach ($this->recap($period) as $row) {
                TeacherHonor::create([
                    'teacher_honor_period_id' => $period->id,
                    'teacher_user_id' => $row['teacher_user_id'],
                    'class_count' => $row['class_count'],
                    'honor_amount' => $row['honor_amount'],
                    'classes' => $row['classes'],
                    'status' => TeacherHonor::STATUS_PENDING,
                ]);
            }

            $period->update([
                'status' => TeacherHonorPeriod::STATUS_CLOSED,
                'closed_by_user_id' => $user->id,
                'closed_at' => now(),
            ]);
        });
    }

    /** Alasan periode belum boleh ditutup; null = boleh. */
    public function closeBlockedReason(TeacherHonorPeriod $period): ?string
    {
        if (! today()->greaterThan($period->end_date)) {
            return 'Periode ini baru bisa ditutup mulai '.$period->end_date->copy()->addDay()->translatedFormat('d F Y').', setelah tanggal tutupnya lewat.';
        }

        $pending = $this->sessionsIn($period)
            ->whereIn('status', [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($pending->isEmpty()) {
            return null;
        }

        $labels = [ClassSession::STATUS_WAITING_TEACHER => 'menunggu pengajar', ClassSession::STATUS_WAITING_ADMIN => 'menunggu admin'];
        $parts = $pending->map(fn ($total, $status) => $total.' '.$labels[$status])->implode(', ');

        return 'Masih ada '.$pending->sum().' pengajuan kelas di periode ini yang belum selesai ('.$parts.'). Setujui atau tolak dulu sebelum periode ditutup, supaya honor pengajar tidak ada yang terlewat.';
    }

    /**
     * Isian otomatis periode berikutnya per cabang: mulai sehari setelah
     * periode terakhir berakhir, selama satu bulan (26 Sep -> 25 Okt).
     *
     * @return array<string, array{start: string, end: string}> branch_id => tanggal Y-m-d
     */
    public function nextPeriodDates(): array
    {
        return TeacherHonorPeriod::groupBy('branch_id')
            ->selectRaw('branch_id, MAX(end_date) as last_end')
            ->pluck('last_end', 'branch_id')
            ->map(function ($lastEnd) {
                $start = Carbon::parse($lastEnd)->addDay();

                return ['start' => $start->toDateString(), 'end' => $start->copy()->addMonthNoOverflow()->subDay()->toDateString()];
            })
            ->all();
    }

    /** Periode baru tidak boleh bentrok atau menyisakan tanggal bolong dengan periode lain di cabang yang sama. */
    public function validateNewPeriod(string $branchId, Carbon $start, Carbon $end): ?string
    {
        $periods = fn () => TeacherHonorPeriod::where('branch_id', $branchId);
        $format = fn ($date) => Carbon::parse($date)->translatedFormat('d F Y');

        if ($periods()->where('start_date', '<=', $end->toDateString())->where('end_date', '>=', $start->toDateString())->exists()) {
            return 'Tanggalnya bentrok dengan periode lain di cabang yang sama. Coba pilih rentang tanggal lain ya.';
        }

        $previousEnd = $periods()->where('end_date', '<', $start->toDateString())->max('end_date');

        if ($previousEnd && ! Carbon::parse($previousEnd)->addDay()->isSameDay($start)) {
            return 'Periode sebelumnya berakhir '.$format($previousEnd).', jadi periode baru harus mulai '.$format(Carbon::parse($previousEnd)->addDay()).' supaya tidak ada tanggal yang terlewat.';
        }

        $nextStart = $periods()->where('start_date', '>', $end->toDateString())->min('start_date');

        if ($nextStart && ! Carbon::parse($nextStart)->subDay()->isSameDay($end)) {
            return 'Periode berikutnya mulai '.$format($nextStart).', jadi periode ini harus berakhir '.$format(Carbon::parse($nextStart)->subDay()).' supaya tidak ada tanggal yang terlewat.';
        }

        return null;
    }

    public function approveForPayout(TeacherHonor $honor, User $manager): TeacherHonor
    {
        $this->assertStatus($honor, TeacherHonor::STATUS_PENDING);

        $honor->update([
            'status' => TeacherHonor::STATUS_APPROVED_FOR_PAYOUT,
            'approved_by_user_id' => $manager->id,
            'approved_at' => now(),
        ]);

        return $honor->fresh();
    }

    public function markPaid(TeacherHonor $honor): TeacherHonor
    {
        $this->assertStatus($honor, TeacherHonor::STATUS_APPROVED_FOR_PAYOUT);

        $honor->update([
            'status' => TeacherHonor::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $honor->fresh();
    }

    /** Sesi kelas di cabang & rentang tanggal periode (berdasarkan waktu pengajuan). */
    private function sessionsIn(TeacherHonorPeriod $period): Builder
    {
        return ClassSession::where('branch_id', $period->branch_id)
            ->whereBetween('requested_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()]);
    }

    /**
     * Kelas (pembelian paket) tempat sesi ini dihitung -- SELALU satu, supaya
     * 1 sesi tidak pernah terhitung 2 kelas. Kalau potongannya terbagi ke 2
     * pembelian (sisa pembelian lama + pembelian baru), sesi masuk ke
     * pembelian dengan porsi credit terbesar (sama besar: yang lebih dulu).
     * Data lama tanpa alokasi jatuh ke paket di sesi itu.
     *
     * @return array{0: string, 1: array{owner: string, package: string, class_name: string, fee: float}}
     */
    private function creditSource(ClassSession $session): array
    {
        $allocation = collect($session->courseCredit?->allocations ?? [])
            ->filter(fn ($allocation) => $allocation->purchase && (float) $allocation->amount > 0)
            ->sortByDesc(fn ($allocation) => (float) $allocation->amount)
            ->first();

        if ($allocation) {
            return ['purchase:'.$allocation->purchase->id, $this->source(
                $this->studentName($allocation->purchase->student),
                $allocation->purchase->coursePackage
            )];
        }

        return ['package:'.$session->student_id.':'.$session->course_package_id, $this->source(
            $this->studentName($session->student),
            $session->coursePackage
        )];
    }

    private function source(string $owner, $package): array
    {
        return [
            'owner' => $owner,
            'package' => $package?->name ?? '-',
            'class_name' => $package?->courseClass?->name ?? '-',
            'fee' => (float) ($package?->courseClass?->teacher_fee ?? 0),
        ];
    }

    private function studentName($student): string
    {
        return $student ? trim($student->first_name.' '.$student->last_name) : '-';
    }

    private function assertStatus(TeacherHonor $honor, string $expectedStatus): void
    {
        if ($honor->status !== $expectedStatus) {
            throw new InvalidTeacherHonorStateException('Status honor ini sudah berubah, silakan muat ulang halaman.');
        }
    }
}
