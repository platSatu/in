<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === CLASS SESSIONS (Pengajuan Pemakaian Credit) ===
| FASE 2 (16 September 2026, hasil diskusi + tambahan requirement langsung
| dari owner lewat WhatsApp): 1 baris = 1 pengajuan pemakaian credit untuk
| kelas yang akan/sedang berlangsung.
|
| Alur statusnya (SENGAJA siswa yang trigger duluan, BUKAN pengajar --
| lihat App\Services\ClassSession\ClassSessionWorkflowService untuk
| penjelasan lengkap kenapa urutan ini penting dari sisi kontrol/insentif):
|
|   menunggu_guru --(guru approve)--> menunggu_admin --(admin approve)--> disetujui
|                 \-(guru tolak)--> ditolak_guru      \-(admin tolak)--> ditolak_admin
|
| Credit BENAR-BENAR terpotong (lewat App\Services\CourseCredit\
| CourseCreditDebitService::debit(), source_type=SESSION_DEBIT) HANYA pada
| saat status berubah jadi 'disetujui' -- bukan lebih awal. Kalau siswa
| tidak jadi mengajukan / guru menolak / admin menolak, credit tidak pernah
| tersentuh sama sekali.
|
| `credit_amount_requested` vs `credit_amount_final` -- dipisah karena
| admin "bisa punya fungsi mengubah besaran kredit yang diajukan apabila
| ada kesalahan pengajuan" (requirement eksplisit owner). Kolom
| `credit_amount_requested` TIDAK PERNAH diubah (jejak audit apa yang
| SEBENARNYA diajukan siswa), `credit_amount_final` diisi admin saat
| approval (default sama dengan yang diajukan kalau tidak ada koreksi).
|
| `course_credit_id` diisi belakangan (nullable) begitu admin approve &
| pemotongan credit berhasil -- jadi jembatan audit 2 arah antara
| pengajuan ini dan baris ledger debit yang dihasilkannya.
|
| `student_id` & `teacher_user_id` SENGAJA tanpa foreign key constraint
| (char(36) + index biasa) -- mengikuti pola yang sama sudah dipakai
| course_package_purchases.student_id & course_credits.student_id di
| codebase ini.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_sessions')) {
            return;
        }

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('student_id', 36)->index();
            $table->char('teacher_user_id', 36)->index();

            $table->foreignUuid('course_package_id')
                ->constrained('course_packages')
                ->restrictOnDelete();

            $table->foreignUuid('branch_id')
                ->nullable()
                ->constrained('company_branch')
                ->nullOnDelete();

            $table->decimal('credit_amount_requested', 4, 2);
            $table->decimal('credit_amount_final', 4, 2)->nullable();

            // 'menunggu_guru' (default, baru diajukan siswa) -> 'ditolak_guru'
            // atau 'menunggu_admin' -> 'ditolak_admin' atau 'disetujui'.
            // VARCHAR polos (bukan native ENUM), ikut pola course_credits.source_type
            // supaya menambah status baru nanti cukup migration kecil.
            $table->string('status', 30)->default('menunggu_guru');

            $table->text('notes')->nullable();

            $table->foreignUuid('course_credit_id')
                ->nullable()
                ->constrained('course_credits')
                ->nullOnDelete();

            $table->timestamp('requested_at');
            $table->timestamp('teacher_approved_at')->nullable();
            $table->timestamp('admin_approved_at')->nullable();

            $table->char('admin_user_id', 36)->nullable()->index();

            $table->timestamps();

            $table->index(['student_id', 'status'], 'cs_student_status_idx');
            $table->index(['teacher_user_id', 'status'], 'cs_teacher_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
