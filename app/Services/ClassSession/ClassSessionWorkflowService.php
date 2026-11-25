<?php

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\CourseCredit;
use App\Models\CompanyBranch;
use App\Models\Student;
use App\Models\TeacherAvailability;
use App\Models\TeacherHonorPeriod;
use App\Models\User;
use App\Services\CourseCredit\CourseCreditDebitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * FASE 2 (16 September 2026) -- satu-satunya pintu untuk menjalankan alur
 * "Pengajuan Pemakaian Credit" dari awal sampai credit benar-benar
 * terpotong. Requirement ASLI dari owner (diteruskan lewat WhatsApp):
 *
 *   "Utk pemakaian kredit, jadi murid harus mengajukan utk dipakai, guru
 *    approve setiap masuk kelas dan kelas dimulai. Nanti final approval
 *    dari admin, admin ini bisa punya fungsi mengubah besaran kredit yang
 *    diajukan apabila ada kesalahan pengajuan. Murid bisa mengajukan
 *    pemakaian kredit dengan besaran 1 atau 1.5 (1 credit = 1 jam). Kalau
 *    ternyata di hari tersebut belajar 2 jam berarti ngajuin 2 kali 1
 *    kredit."
 *   (12 Oktober 2026: owner menambah pilihan 2 credit, jadi belajar 2 jam
 *    cukup 1 pengajuan. Validasinya di StudentPortal\ClassSessionController.)
 *
 * KENAPA SISWA YANG TRIGGER DULUAN (bukan guru seperti desain absensi
 * awal yang sempat didiskusikan): pengajar itu sendiri yang DIUNTUNGKAN
 * secara finansial dari jumlah credit yang terpakai (jadi dasar hitung
 * Honor Pengajar, fitur menyusul) -- kalau pengajar yang trigger duluan,
 * itu rawan disalahgunakan. Dengan siswa (pihak yang MEMBAYAR, creditnya
 * berkurang) yang mengajukan duluan, dan pengajar cuma menyetujui, kontrol
 * insentifnya lebih aman.
 *
 * KENAPA CREDIT BARU DIPOTONG SAAT admin approve (bukan saat guru
 * approve): supaya admin sungguh-sungguh jadi GERBANG TERAKHIR yang bisa
 * mengoreksi kesalahan pengajuan SEBELUM credit siswa benar-benar
 * berkurang -- begitu status 'disetujui', pemotongannya final & tercatat
 * lewat CourseCreditDebitService (dengan pelacakan asal pembelian FIFO,
 * lihat Fase 1).
 *
 * Honor Pengajar TIDAK dicatat per sesi di sini -- dihitung per periode
 * dari sesi yang disetujui (1 kelas = 1 credit x fee Course Class), lihat
 * App\Services\TeacherHonor\TeacherHonorService.
 */
class ClassSessionWorkflowService
{
    public function __construct(
        private readonly CourseCreditDebitService $debitService = new CourseCreditDebitService()
    ) {
    }

    /** Jam mulai kelas baru dibuat per kelipatan ini, supaya siswa mudah bertemu di jam yang sama. */
    public const START_STEP_MINUTES = 60;

    /** Siswa bisa memilih jadwal sampai sekian hari ke depan. */
    public const BOOKING_DAYS_AHEAD = 30;

    /**
     * Siswa mengajukan pemakaian credit untuk kelas di jam yang ia pilih.
     * BELUM memotong credit sama sekali -- baru status 'menunggu_guru'.
     *
     * Jam kelas ($classStartAt) wajib ada di pilihan scheduleOptions():
     * gabung ke kelas yang kuotanya masih ada, atau buka jam baru di dalam
     * jam buka pengajar. Dicek ulang di sini (bukan hanya di tampilan) dan
     * pengajar dikunci, supaya dua siswa yang submit bersamaan tidak bisa
     * melebihi kuota.
     */
    public function requestUsage(
        Student $student,
        User $teacher,
        CoursePackage $package,
        float $creditAmountRequested,
        Carbon $classStartAt,
        ?CompanyBranch $branch = null,
        ?string $notes = null
    ): ClassSession {
        if ($creditAmountRequested <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit yang diajukan harus lebih besar dari 0.');
        }

        $this->assertNoPendingTradeIn($student, $package->id);

        return DB::transaction(function () use ($student, $teacher, $package, $creditAmountRequested, $classStartAt, $branch, $notes) {
            User::whereKey($teacher->id)->lockForUpdate()->first();

            $available = $this->creditAvailable($student, $package);

            if ($available + 0.000001 < $creditAmountRequested) {
                throw new InvalidArgumentException('Sisa credit paket "'.$package->name.'" tidak cukup (tersedia '.max(0, $available).' credit, termasuk yang sedang diajukan).');
            }

            $this->assertTimeIsOffered($student, $teacher, $package, $classStartAt, $creditAmountRequested);

            return ClassSession::create([
                'student_id' => $student->id,
                'teacher_user_id' => $teacher->id,
                'course_package_id' => $package->id,
                'branch_id' => $branch?->id,
                'credit_amount_requested' => $creditAmountRequested,
                'status' => ClassSession::STATUS_WAITING_TEACHER,
                'notes' => $notes,
                'requested_at' => now(),
                'class_start_at' => $classStartAt,
                'class_end_at' => $this->classEnd($classStartAt, $creditAmountRequested),
            ]);
        });
    }

    /**
     * Jadwal berulang (25 November 2026): hari & jam yang sama setiap minggu, mulai dari jadwal pertama
     * sampai akhir periode (tanggal 25, lihat TeacherHonorService::cycleFor()). Setiap tanggal dicek
     * dengan aturan yang sama persis seperti 1 pengajuan; yang tidak tersedia ditampilkan alasannya.
     *
     * @return array<int, array{start: string, label: string, ok: bool, reason: ?string}>
     */
    public function previewRecurring(Student $student, User $teacher, CoursePackage $package, Carbon $firstStart, float $credit): array
    {
        $creditLeft = $this->creditAvailable($student, $package);
        $rows = [];

        foreach ($this->weeklyStartsInPeriod($firstStart) as $start) {
            $options = $this->scheduleOptions($student, $teacher, $package, $start, $credit);
            $offered = collect($options['join'])->merge($options['new'])->pluck('start')->contains($start->format('Y-m-d H:i'));

            $reason = match (true) {
                ! $offered => 'jam ini penuh, bentrok, atau di luar jam buka pengajar',
                $creditLeft + 0.000001 < $credit => 'credit tidak cukup',
                default => null,
            };

            if ($reason === null) {
                $creditLeft -= $credit;
            }

            $rows[] = [
                'start' => $start->format('Y-m-d H:i'),
                'label' => $start->translatedFormat('D, d M Y').' · '.$start->format('H:i').' - '.$this->classEnd($start, $credit)->format('H:i'),
                'ok' => $reason === null,
                'reason' => $reason,
            ];
        }

        return $rows;
    }

    /**
     * Ajukan semua tanggal berulang sekaligus. Tiap tanggal tetap lewat requestUsage() (dicek ulang di dalam
     * lock), jadi hasilnya sama aman dengan mengajukan satu per satu. Tanggal yang gagal dilewati saja.
     *
     * @return array{created: int, skipped: array<int, string>}
     */
    public function requestRecurring(Student $student, User $teacher, CoursePackage $package, float $credit, Carbon $firstStart, ?CompanyBranch $branch = null, ?string $notes = null): array
    {
        $created = 0;
        $skipped = [];

        foreach ($this->weeklyStartsInPeriod($firstStart) as $start) {
            try {
                $this->requestUsage($student, $teacher, $package, $credit, $start, $branch, $notes);
                $created++;
            } catch (InvalidArgumentException $e) {
                $skipped[] = $start->translatedFormat('D, d M').' ('.(str_contains($e->getMessage(), 'credit') ? 'credit tidak cukup' : 'jam tidak tersedia').')';
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** Jadwal pertama + minggu-minggu berikutnya di hari & jam yang sama, sampai akhir periodenya (tanggal 25). */
    public function weeklyStartsInPeriod(Carbon $firstStart): array
    {
        $periodEnd = Carbon::parse((new \App\Services\TeacherHonor\TeacherHonorService())->cycleFor($firstStart->copy())['end'])->endOfDay();
        $starts = [];

        for ($start = $firstStart->copy(); $start->lte($periodEnd); $start = $start->copy()->addWeek()) {
            $starts[] = $start;
        }

        return $starts;
    }

    /** Sisa credit paket ini dikurangi pengajuan yang belum selesai (credit terpisah per paket). */
    private function creditAvailable(Student $student, CoursePackage $package): float
    {
        $purchases = CoursePackagePurchase::where('student_id', $student->id)->where('course_package_id', $package->id)->get();
        $reserved = (float) ClassSession::where('student_id', $student->id)
            ->where('course_package_id', $package->id)
            ->whereIn('status', [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN])
            ->sum('credit_amount_requested');

        return array_sum($this->debitService->remainingByPurchase($purchases)) - $reserved;
    }

    /**
     * Admin memindahkan jadwal 1 siswa (mis. siswa ke-7 sakit) ke jam lain.
     * Siswa lain di kelas lamanya tidak ikut pindah. Lama kelas & credit
     * tetap. Admin boleh memilih jam di luar jam buka pengajar, tapi tetap
     * tidak boleh bentrok dengan kelas lain pengajar itu atau melebihi kuota.
     */
    public function reschedule(ClassSession $session, Carbon $newStartAt, User $admin): ClassSession
    {
        return DB::transaction(function () use ($session, $newStartAt, $admin) {
            $session = ClassSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($session, [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN]);

            $teacher = User::whereKey($session->teacher_user_id)->lockForUpdate()->firstOrFail();
            $credit = (float) $session->credit_amount_requested;
            $oldLabel = optional($session->classStartsAt())->translatedFormat('d M Y H:i');

            $this->assertTimeIsOffered($session->student, $teacher, $session->coursePackage, $newStartAt, $credit, $session);

            $session->update([
                'class_start_at' => $newStartAt,
                'class_end_at' => $this->classEnd($newStartAt, $credit),
                'notes' => trim(($session->notes ? $session->notes."\n" : '').'Jadwal dipindah admin '.$admin->name.' dari '.$oldLabel.' ke '.$newStartAt->translatedFormat('d M Y H:i').'.'),
            ]);

            return $session->fresh();
        });
    }

    /**
     * Pilihan jam kelas untuk 1 tanggal -- dipakai form Ajukan Kredit DAN
     * pengecekan saat submit, supaya aturannya hanya ada di satu tempat.
     *
     *  - join: kelas yang sudah dibuka siswa lain (pengajar, paket & lama
     *          kelas sama) dan kuotanya belum penuh.
     *  - new:  jam mulai baru di dalam jam buka pengajar yang tidak bentrok
     *          dengan kelas lain pengajar itu maupun kelas siswa sendiri.
     *
     * Saat admin memindah jadwal: $moving = pengajuan yang dipindah (tidak
     * dihitung bentrok dengan dirinya sendiri) dan $adminChosenStart = jam
     * pilihan admin, yang boleh di luar jam buka pengajar.
     *
     * @return array{join: array<int, array{start: string, label: string, filled: int, quota: int}>, new: array<int, array{start: string, label: string}>}
     */
    public function scheduleOptions(
        Student $student,
        User $teacher,
        CoursePackage $package,
        Carbon $date,
        float $credit,
        ?ClassSession $moving = null,
        ?Carbon $adminChosenStart = null
    ): array {
        $options = ['join' => [], 'new' => []];
        $date = $date->copy()->startOfDay();
        $minutes = (int) round($credit * 60);

        if ($minutes <= 0 || ! isset(TeacherAvailability::DAYS[$date->dayOfWeekIso]) || $date->lt(today())) {
            return $options;
        }

        // Semua kelas pengajar ini di tanggal itu (semua paket & cabang), dikelompokkan per kelas.
        $teacherClasses = $this->activeSessionsOn($date)
            ->where('teacher_user_id', $teacher->id)
            ->get()
            ->reject(fn (ClassSession $session) => $session->id === $moving?->id)
            ->groupBy(fn (ClassSession $session) => $session->classKey());

        $studentSessions = $this->activeSessionsOn($date)
            ->where('student_id', $student->id)
            ->get()
            ->reject(fn (ClassSession $session) => $session->id === $moving?->id);

        $studentIsFree = fn (Carbon $start, Carbon $end) => $studentSessions
            ->doesntContain(fn (ClassSession $session) => $this->overlaps($start, $end, $session->class_start_at, $session->class_end_at));

        // 1. Gabung ke kelas yang sudah ada.
        foreach ($teacherClasses as $students) {
            $first = $students->first();
            $start = $first->class_start_at;
            $end = $first->class_end_at;

            $samePackageAndLength = $first->course_package_id === $package->id
                && (int) round($start->diffInMinutes($end)) === $minutes;

            if ($samePackageAndLength && $start->isFuture() && $students->count() < $package->seatsPerClass() && $studentIsFree($start, $end)) {
                $options['join'][] = [
                    'start' => $start->format('Y-m-d H:i'),
                    'label' => $start->format('H:i').' - '.$end->format('H:i'),
                    'filled' => $students->count(),
                    'quota' => $package->seatsPerClass(),
                ];
            }
        }

        // 2. Buka jam baru.
        $openingHours = $adminChosenStart
            ? [[$adminChosenStart->copy(), $adminChosenStart->copy()->addMinutes($minutes)]]
            : $this->openingHours($teacher, $date, $student->branch_id);

        $teacherIsFree = fn (Carbon $start, Carbon $end) => $teacherClasses
            ->doesntContain(fn (Collection $students) => $this->overlaps($start, $end, $students->first()->class_start_at, $students->first()->class_end_at));

        foreach ($openingHours as [$open, $close]) {
            for ($start = $open->copy(); $start->copy()->addMinutes($minutes)->lte($close); $start->addMinutes(self::START_STEP_MINUTES)) {
                $end = $start->copy()->addMinutes($minutes);

                if ($start->isFuture() && $teacherIsFree($start, $end) && $studentIsFree($start, $end)) {
                    $options['new'][] = ['start' => $start->format('Y-m-d H:i'), 'label' => $start->format('H:i').' - '.$end->format('H:i')];
                }
            }
        }

        usort($options['join'], fn ($a, $b) => strcmp($a['start'], $b['start']));

        return $options;
    }

    /**
     * Jam buka pengajar di hari itu untuk cabang siswa (siswa tanpa cabang:
     * semua cabang pengajar).
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    private function openingHours(User $teacher, Carbon $date, ?string $branchId): array
    {
        return TeacherAvailability::where('teacher_user_id', $teacher->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('start_time')
            ->get()
            ->map(fn (TeacherAvailability $row) => [
                $date->copy()->setTimeFromTimeString($row->start_time),
                $date->copy()->setTimeFromTimeString($row->end_time),
            ])
            ->all();
    }

    private function assertTimeIsOffered(
        Student $student,
        User $teacher,
        CoursePackage $package,
        Carbon $classStartAt,
        float $credit,
        ?ClassSession $moving = null
    ): void {
        $options = $this->scheduleOptions($student, $teacher, $package, $classStartAt, $credit, $moving, $moving ? $classStartAt : null);
        $chosen = $classStartAt->format('Y-m-d H:i');

        if (collect($options['join'])->pluck('start')->contains($chosen) || collect($options['new'])->pluck('start')->contains($chosen)) {
            return;
        }

        throw new InvalidArgumentException('Jam '.$classStartAt->translatedFormat('l, d M Y H:i').' sudah tidak tersedia (kuota penuh, bentrok dengan kelas lain, atau di luar jam buka pengajar). Silakan pilih jam lain.');
    }

    /** Pengajuan yang masih memegang kursi kelas pada tanggal itu. */
    private function activeSessionsOn(Carbon $date)
    {
        return ClassSession::whereIn('status', ClassSession::ACTIVE_STATUSES)
            ->whereNotNull('class_start_at')
            ->whereBetween('class_start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }

    private function overlaps(Carbon $start, Carbon $end, ?Carbon $otherStart, ?Carbon $otherEnd): bool
    {
        return $otherStart && $otherEnd && $start->lt($otherEnd) && $end->gt($otherStart);
    }

    /** 1 credit = 1 jam, 1.5 credit = 1 jam 30 menit. */
    private function classEnd(Carbon $start, float $credit): Carbon
    {
        return $start->copy()->addMinutes((int) round($credit * 60));
    }

    /**
     * Pengajar approve PERSIS saat kelas dimulai -- lihat docblock class di
     * atas. Belum memotong credit, cuma memindahkan ke antrian admin.
     */
    public function teacherApprove(ClassSession $session, User $teacher): ClassSession
    {
        $this->assertStatus($session, ClassSession::STATUS_WAITING_TEACHER);

        if ($session->teacher_user_id !== $teacher->id) {
            throw new InvalidArgumentException('Pengajuan ini bukan untuk pengajar yang sedang login.');
        }

        $session->update([
            'status' => ClassSession::STATUS_WAITING_ADMIN,
            'teacher_approved_at' => now(),
        ]);

        return $session->fresh();
    }

    /**
     * Siswa membatalkan pengajuannya sendiri (14 Oktober 2026). Hanya boleh
     * selama masih 'menunggu_guru' -- credit belum terpotong, jadi tidak ada
     * transaksi apa pun. Setelah pengajar menyetujui (kelas sudah dimulai),
     * pembatalan harus lewat admin (Tolak), supaya siswa tidak bisa
     * membatalkan kelas yang sudah diikuti.
     */
    public function studentCancel(ClassSession $session, Student $student): ClassSession
    {
        return DB::transaction(function () use ($session, $student) {
            $session = ClassSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->student_id !== $student->id) {
                throw new InvalidArgumentException('Pengajuan ini bukan milik Anda.');
            }

            $this->assertStatus($session, ClassSession::STATUS_WAITING_TEACHER);

            $session->update(['status' => ClassSession::STATUS_CANCELLED_BY_STUDENT]);

            return $session->fresh();
        });
    }

    public function teacherReject(ClassSession $session, User $teacher, ?string $reason = null): ClassSession
    {
        $this->assertStatus($session, ClassSession::STATUS_WAITING_TEACHER);

        if ($session->teacher_user_id !== $teacher->id) {
            throw new InvalidArgumentException('Pengajuan ini bukan untuk pengajar yang sedang login.');
        }

        $session->update([
            'status' => ClassSession::STATUS_REJECTED_BY_TEACHER,
            'notes' => $reason,
        ]);

        return $session->fresh();
    }

    /**
     * Admin memberi approval FINAL -- di sinilah credit BENAR-BENAR
     * dipotong (lewat CourseCreditDebitService, source_type=SESSION_DEBIT).
     * $correctedCreditAmount diisi kalau admin mengoreksi besaran yang
     * diajukan siswa (requirement eksplisit owner); null berarti dipakai
     * apa adanya sesuai yang diajukan.
     *
     * @throws \App\Services\CourseCredit\InsufficientCourseCreditException
     *         kalau ternyata saldo credit student tidak cukup -- ClassSession
     *         TETAP di status 'menunggu_admin' (tidak ikut berubah) supaya
     *         admin bisa coba lagi setelah masalah saldo diselesaikan,
     *         bukan langsung dianggap gagal permanen.
     */
    public function adminApprove(ClassSession $session, User $admin, ?float $correctedCreditAmount = null, ?string $notes = null): ClassSession
    {
        $this->assertStatus($session, ClassSession::STATUS_WAITING_ADMIN);

        $finalAmount = $correctedCreditAmount ?? (float) $session->credit_amount_requested;

        if ($finalAmount <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit final harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($session, $admin, $finalAmount, $notes) {
            // Kunci sesi & siswa lalu cek ulang: dua admin yang menekan approve
            // bersamaan tidak boleh memotong credit dua kali, dan credit yang
            // sedang dikunci upgrade tidak boleh terpakai.
            $session = ClassSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($session, ClassSession::STATUS_WAITING_ADMIN);
            $student = Student::whereKey($session->student_id)->lockForUpdate()->firstOrFail();
            $this->assertNoPendingTradeIn($student, $session->course_package_id);

            $description = sprintf(
                'Pemakaian kelas (%s) -- pengajuan #%s',
                optional($session->coursePackage)->name ?? 'Package',
                Str::limit($session->id, 8, '')
            );

            // Credit terpisah per paket: hanya dipotong dari pembelian paket kelas ini.
            $debit = $this->debitService->debit($student, $finalAmount, CourseCredit::SOURCE_SESSION_DEBIT, $description, $session->course_package_id);

            $session->update([
                'status' => ClassSession::STATUS_APPROVED,
                'credit_amount_final' => $finalAmount,
                'course_credit_id' => $debit->id,
                'admin_approved_at' => now(),
                'admin_user_id' => $admin->id,
                'notes' => $notes ?? $session->notes,
            ]);

            return $session->fresh(['courseCredit.allocations']);
        });
    }

    /**
     * Admin juga boleh menolak pengajuan yang masih menunggu pengajar
     * (mis. pengajar tidak merespons), supaya periode Honor Pengajar bisa
     * ditutup -- lihat TeacherHonorService::closeBlockedReason(). Credit
     * belum terpotong di kedua status ini, jadi aman ditolak.
     */
    public function adminReject(ClassSession $session, User $admin, ?string $reason = null): ClassSession
    {
        return DB::transaction(function () use ($session, $admin, $reason) {
            // Dikunci supaya tidak bisa menimpa sesi yang barusan disetujui (credit sudah terpotong).
            $session = ClassSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($session, [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN]);

            $session->update([
                'status' => ClassSession::STATUS_REJECTED_BY_ADMIN,
                'notes' => $reason,
                'admin_user_id' => $admin->id,
            ]);

            return $session->fresh();
        });
    }

    /**
     * Refund sesi yang sudah disetujui: credit kembali ke pembelian asalnya
     * dan sesi tidak lagi dihitung honor. Ditolak kalau periode honor yang
     * mencakup tanggal sesi sudah ditutup (honornya sudah terkunci).
     */
    public function refund(ClassSession $session, User $admin, string $reason): ClassSession
    {
        return DB::transaction(function () use ($session, $admin, $reason) {
            $session = ClassSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($session, ClassSession::STATUS_APPROVED);

            if (! $session->course_credit_id) {
                throw new InvalidClassSessionStateException('Sesi ini tidak punya catatan potongan credit, jadi tidak bisa di-refund.');
            }

            $this->assertPeriodOpen($session->branch_id, $session->classStartsAt());

            $refund = $this->debitService->refund(
                $session->courseCredit()->firstOrFail(),
                sprintf('Refund kelas (%s) -- pengajuan #%s', optional($session->coursePackage)->name ?? 'Package', Str::limit($session->id, 8, ''))
            );

            $session->update([
                'status' => ClassSession::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refunded_by_user_id' => $admin->id,
                'refund_reason' => $reason,
                'refund_course_credit_id' => $refund->id,
            ]);

            return $session->fresh();
        });
    }

    /**
     * Admin memotong credit langsung (mis. siswa tidak hadir, diganti video,
     * dianggap hadir). Tercatat sebagai sesi 'disetujui' berlabel admin.
     * Pengajar opsional: kalau diisi, sesi ini masuk honor pengajar itu.
     */
    public function adminCharge(Student $student, CoursePackage $package, ?User $teacher, float $amount, Carbon $classAt, string $reason, User $admin): ClassSession
    {
        if ($amount <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit harus lebih besar dari 0.');
        }

        if ($classAt->isFuture()) {
            throw new InvalidArgumentException('Tanggal kelas tidak boleh di masa depan.');
        }

        if ($teacher && ! $teacher->hasRole('teacher')) {
            throw new InvalidArgumentException('Pengajar yang dipilih tidak valid.');
        }

        // Cabang sesi = cabang siswa (dipakai rekap honor & batasan per cabang).
        $branchId = $student->branch_id;

        return DB::transaction(function () use ($student, $package, $teacher, $amount, $classAt, $reason, $admin, $branchId) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $this->assertPeriodOpen($branchId, $classAt);
            $this->assertNoPendingTradeIn($student, $package->id);

            $session = ClassSession::create([
                'student_id' => $student->id,
                'teacher_user_id' => $teacher?->id,
                'course_package_id' => $package->id,
                'branch_id' => $branchId,
                'credit_amount_requested' => $amount,
                'status' => ClassSession::STATUS_WAITING_ADMIN,
                'notes' => $reason,
                'requested_at' => $classAt,
                'is_admin_entry' => true,
            ]);

            $debit = $this->debitService->debit(
                $student,
                $amount,
                CourseCredit::SOURCE_SESSION_DEBIT,
                sprintf('Potong credit oleh admin (%s) -- %s', $package->name, Str::limit($reason, 60)),
                $package->id
            );

            $session->update([
                'status' => ClassSession::STATUS_APPROVED,
                'credit_amount_final' => $amount,
                'course_credit_id' => $debit->id,
                'admin_approved_at' => now(),
                'admin_user_id' => $admin->id,
            ]);

            return $session->fresh();
        });
    }

    /**
     * Honor periode yang mencakup tanggal ini sudah dikunci -- data kelasnya
     * tidak boleh berubah lagi. WAJIB dipanggil di dalam transaksi: baris
     * periode dikunci (lockForUpdate), jadi kalau admin lain sedang menutup
     * periode yang sama, salah satunya menunggu -- potongan/refund tidak
     * mungkin terlewat dari rekap yang dikunci.
     */
    private function assertPeriodOpen(?string $branchId, ?\DateTimeInterface $date): void
    {
        if (! $date) {
            return;
        }

        $closed = TeacherHonorPeriod::covering($branchId, $date)
            ->lockForUpdate()
            ->get()
            ->contains(fn (TeacherHonorPeriod $period) => ! $period->isOpen());

        if ($closed) {
            throw new InvalidClassSessionStateException('Periode honor untuk tanggal ini sudah ditutup, jadi datanya tidak bisa diubah lagi.');
        }
    }

    /** Credit paket ini sedang dipakai upgrade yang menunggu pembayaran. */
    private function assertNoPendingTradeIn(Student $student, ?string $coursePackageId): void
    {
        $pending = CoursePackagePayment::awaitingTradeIn()
            ->where('student_id', $student->id)
            ->whereHas('sourcePurchase', fn ($query) => $query->where('course_package_id', $coursePackageId))
            ->exists();

        if ($pending) {
            throw new InvalidArgumentException('Credit paket ini sedang dipakai untuk upgrade yang menunggu pembayaran. Selesaikan atau tunggu pembayaran itu kedaluwarsa dulu.');
        }
    }

    /** @param string|array<int, string> $expectedStatus */
    private function assertStatus(ClassSession $session, string|array $expectedStatus): void
    {
        if (! in_array($session->status, (array) $expectedStatus, true)) {
            throw new InvalidClassSessionStateException(
                "Pengajuan ini statusnya '{$session->status}', bukan '".implode("'/'", (array) $expectedStatus)."' -- aksi tidak bisa dilakukan."
            );
        }
    }
}
