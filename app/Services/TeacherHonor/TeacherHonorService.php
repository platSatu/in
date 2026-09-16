<?php

namespace App\Services\TeacherHonor;

use App\Helpers\MoneyMath;
use App\Models\ClassSession;
use App\Models\TeacherCommissionRate;
use App\Models\TeacherHonor;
use App\Models\User;
use InvalidArgumentException;

/**
 * FASE 3 (16 September 2026, hasil diskusi Honor Pengajar) -- satu-satunya
 * pintu untuk menghitung & mencatat honor pengajar dari 1 ClassSession yang
 * sudah disetujui admin.
 *
 * KENAPA honor TIDAK FLAT (requirement eksplisit owner): dihitung dari
 * persentase komisi pengajar itu sendiri (bisa beda-beda tiap orang, lihat
 * TeacherCommissionRate) dikali NILAI RUPIAH credit yang benar-benar
 * terpakai di sesi itu -- nilai ini diambil dari
 * course_credit_allocations (dibuat CourseCreditDebitService, Fase 1),
 * yang sudah tahu persis "credit ini asalnya dari pembelian paket mana,
 * harganya berapa" -- SEHINGGA honor pengajar otomatis mengikuti harga
 * paket ASLI yang dipakai siswa itu, bukan harga sembarang.
 *
 * KAPAN dipanggil: SELALU dari
 * ClassSessionWorkflowService::adminApprove(), di DALAM transaction yang
 * sama dengan pemotongan credit -- supaya credit terpotong & honor
 * tercatat selalu sepasang (tidak mungkin salah satu doang berhasil).
 */
class TeacherHonorService
{
    /**
     * @param ClassSession $session Harus sudah berstatus 'disetujui' dan
     *        sudah punya course_credit_id terisi (dipastikan oleh
     *        ClassSessionWorkflowService::adminApprove() sebelum
     *        memanggil ini).
     */
    public function recordForSession(ClassSession $session): TeacherHonor
    {
        $courseCredit = $session->courseCredit()->with('allocations')->first();

        if (!$courseCredit) {
            throw new InvalidArgumentException(
                'ClassSession belum punya baris CourseCredit -- honor cuma bisa dihitung SETELAH credit benar-benar terpotong.'
            );
        }

        $creditValue = MoneyMath::floorToScale(
            (float) $courseCredit->allocations->sum(fn ($allocation) => (float) $allocation->value),
            2
        );

        $percentage = TeacherCommissionRate::rateFor($session->teacher_user_id);

        $honorAmount = MoneyMath::floorToScale($creditValue * ($percentage / 100), 2);

        return TeacherHonor::create([
            'class_session_id' => $session->id,
            'teacher_user_id' => $session->teacher_user_id,
            'student_id' => $session->student_id,
            'branch_id' => $session->branch_id,
            'commission_percentage' => $percentage,
            'credit_value' => $creditValue,
            'honor_amount' => $honorAmount,
            'status' => TeacherHonor::STATUS_PENDING,
        ]);
    }

    /**
     * Approval FINANSIAL oleh Manager di level laporan periodik (lihat
     * diskusi Jadwal & Absensi) -- BUKAN approval per ClassSession
     * (itu sudah selesai di ClassSessionWorkflowService::adminApprove()).
     */
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

    private function assertStatus(TeacherHonor $honor, string $expectedStatus): void
    {
        if ($honor->status !== $expectedStatus) {
            throw new InvalidTeacherHonorStateException(
                "Honor ini statusnya '{$honor->status}', bukan '{$expectedStatus}' -- aksi tidak bisa dilakukan."
            );
        }
    }
}
