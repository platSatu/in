<?php

namespace App\Services\ClassSession;

use App\Models\ClassSession;
use App\Models\CoursePackage;
use App\Models\CourseCredit;
use App\Models\CompanyBranch;
use App\Models\Student;
use App\Models\User;
use App\Services\CourseCredit\CourseCreditDebitService;
use App\Services\TeacherHonor\TeacherHonorService;
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
 * FASE 3 (Honor Pengajar): adminApprove() SEKALIGUS memanggil
 * TeacherHonorService::recordForSession() di DALAM transaction yang sama
 * dengan pemotongan credit -- supaya credit terpotong & honor pengajar
 * tercatat selalu sepasang, tidak mungkin salah satu doang berhasil.
 */
class ClassSessionWorkflowService
{
    public function __construct(
        private readonly CourseCreditDebitService $debitService = new CourseCreditDebitService(),
        private readonly TeacherHonorService $honorService = new TeacherHonorService()
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
            $student = $session->student()->firstOrFail();

            $description = sprintf(
                'Pemakaian kelas (%s) -- pengajuan #%s',
                optional($session->coursePackage)->name ?? 'Package',
                Str::limit($session->id, 8, '')
            );

            $debit = $this->debitService->debit($student, $finalAmount, CourseCredit::SOURCE_SESSION_DEBIT, $description);

            $session->update([
                'status' => ClassSession::STATUS_APPROVED,
                'credit_amount_final' => $finalAmount,
                'course_credit_id' => $debit->id,
                'admin_approved_at' => now(),
                'admin_user_id' => $admin->id,
                'notes' => $notes ?? $session->notes,
            ]);

            $this->honorService->recordForSession($session->fresh());

            return $session->fresh(['courseCredit.allocations', 'teacherHonor']);
        });
    }

    public function adminReject(ClassSession $session, User $admin, ?string $reason = null): ClassSession
    {
        $this->assertStatus($session, ClassSession::STATUS_WAITING_ADMIN);

        $session->update([
            'status' => ClassSession::STATUS_REJECTED_BY_ADMIN,
            'notes' => $reason,
            'admin_user_id' => $admin->id,
        ]);

        return $session->fresh();
    }

    private function assertStatus(ClassSession $session, string $expectedStatus): void
    {
        if ($session->status !== $expectedStatus) {
            throw new InvalidClassSessionStateException(
                "Pengajuan ini statusnya '{$session->status}', bukan '{$expectedStatus}' -- aksi tidak bisa dilakukan."
            );
        }
    }
}
