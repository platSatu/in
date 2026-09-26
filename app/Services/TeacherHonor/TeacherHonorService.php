<?php

namespace App\Services\TeacherHonor;

use App\Models\ClassSession;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Models\User;
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

        $sessions = ClassSession::where('status', ClassSession::STATUS_APPROVED)
            ->where('branch_id', $period->branch_id)
            ->whereBetween('requested_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
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
                    foreach ($this->creditSources($session) as $key => $source) {
                        $classes[$key] ??= $source + ['credit_total' => 0.0, 'sessions' => []];
                        $classes[$key]['credit_total'] += (float) $session->credit_amount_final;
                        $classes[$key]['sessions'][] = [
                            'at' => optional($session->requested_at)->format('Y-m-d H:i'),
                            'student' => $this->studentName($session->student),
                            'credit' => (float) $session->credit_amount_final,
                        ];
                    }
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

    /**
     * Paket (credit) asal potongan sesi ini, dikunci per pembelian. Kalau
     * karena data lama tidak ada alokasi, jatuh ke paket di sesi itu.
     *
     * @return array<string, array{owner: string, package: string, class_name: string, fee: float}>
     */
    private function creditSources(ClassSession $session): array
    {
        $sources = [];

        foreach ($session->courseCredit?->allocations ?? [] as $allocation) {
            $purchase = $allocation->purchase;

            if (! $purchase) {
                continue;
            }

            $sources['purchase:'.$purchase->id] = $this->source(
                $this->studentName($purchase->student),
                $purchase->coursePackage
            );
        }

        if ($sources === []) {
            $sources['package:'.$session->student_id.':'.$session->course_package_id] = $this->source(
                $this->studentName($session->student),
                $session->coursePackage
            );
        }

        return $sources;
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
