<?php

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\CourseCredit;
use App\Models\CompanyBranch;
use App\Models\Student;
use App\Models\User;
use App\Services\CourseCredit\CourseCreditDebitService;
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

    /**
     * Siswa mengajukan pemakaian credit untuk 1 jam kelas (atau 1,5 jam,
     * sesuai kesepakatan) dengan pengajar tertentu. BELUM memotong credit
     * sama sekali -- baru status 'menunggu_guru'.
     */
    public function requestUsage(
        Student $student,
        User $teacher,
        CoursePackage $package,
        float $creditAmountRequested,
        ?CompanyBranch $branch = null,
        ?string $notes = null
    ): ClassSession {
        if ($creditAmountRequested <= 0.0) {
            throw new InvalidArgumentException('Jumlah credit yang diajukan harus lebih besar dari 0.');
        }

        $this->assertNoPendingTradeIn($student, $package->id);

        // Credit terpisah per paket: sisa paket ini dikurangi pengajuan lain yang belum selesai.
        $purchases = CoursePackagePurchase::where('student_id', $student->id)->where('course_package_id', $package->id)->get();
        $reserved = (float) ClassSession::where('student_id', $student->id)
            ->where('course_package_id', $package->id)
            ->whereIn('status', [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN])
            ->sum('credit_amount_requested');
        $available = array_sum($this->debitService->remainingByPurchase($purchases)) - $reserved;

        if ($available + 0.000001 < $creditAmountRequested) {
            throw new InvalidArgumentException('Sisa credit paket "'.$package->name.'" tidak cukup (tersedia '.max(0, $available).' credit, termasuk yang sedang diajukan).');
        }

        return ClassSession::create([
            'student_id' => $student->id,
            'teacher_user_id' => $teacher->id,
            'course_package_id' => $package->id,
            'branch_id' => $branch?->id,
            'credit_amount_requested' => $creditAmountRequested,
            'status' => ClassSession::STATUS_WAITING_TEACHER,
            'notes' => $notes,
            'requested_at' => now(),
        ]);
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
