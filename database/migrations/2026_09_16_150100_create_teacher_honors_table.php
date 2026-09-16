<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === TEACHER HONORS (Ledger Honor Pengajar) ===
| FASE 3 (16 September 2026) -- 1 baris = honor 1 pengajar dari 1
| ClassSession yang SUDAH disetujui admin (lihat
| App\Services\TeacherHonor\TeacherHonorService::recordForSession(), yang
| dipanggil otomatis dari ClassSessionWorkflowService::adminApprove() di
| DALAM transaction yang sama dengan pemotongan credit -- supaya credit
| terpotong & honor tercatat selalu sepasang, tidak mungkin salah satu
| doang yang terjadi).
|
| `commission_percentage` & `credit_value` SENGAJA di-snapshot di sini
| (bukan dihitung ulang dari teacher_commission_rates & course_credit_
| allocations setiap saat) -- supaya honor yang sudah tercatat tidak ikut
| berubah kalau rate pengajar diubah admin belakangan, atau ada penyesuaian
| lain di data allocations (SAMA alasannya dengan `unit_price` & `value` di
| migration create_course_credit_allocations_table).
|
| Status approval SENGAJA terpisah dari status ClassSession -- ini
| approval FINANSIAL (Manager approve laporan periodik sebelum dibayarkan,
| lihat diskusi Jadwal & Absensi), bukan approval OPERASIONAL (yang sudah
| selesai begitu ClassSession berstatus 'disetujui'). 1 baris di sini
| SELALU dibuat dengan status 'pending' -- approval Manager & pencatatan
| "sudah dibayar" adalah aksi terpisah menyusul (lihat
| TeacherHonorService::approveForPayout() & markPaid()).
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_honors')) {
            return;
        }

        Schema::create('teacher_honors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // unique() -- 1 ClassSession cuma boleh punya 1 baris honor,
            // cascadeOnDelete() karena baris ini sepenuhnya turunan dari
            // ClassSession itu.
            $table->foreignUuid('class_session_id')
                ->unique()
                ->constrained('class_sessions')
                ->cascadeOnDelete();

            $table->char('teacher_user_id', 36)->index();
            $table->char('student_id', 36)->index();

            $table->foreignUuid('branch_id')
                ->nullable()
                ->constrained('company_branch')
                ->nullOnDelete();

            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('credit_value', 14, 2);
            $table->decimal('honor_amount', 14, 2);

            // 'pending' (baru terhitung) -> 'approved_for_payout' (disetujui
            // Manager, lihat diskusi approval Jadwal & Absensi) -> 'paid'
            // (sudah benar-benar dibayarkan). VARCHAR polos, ikut pola
            // status di class_sessions & course_credits.source_type.
            $table->string('status', 30)->default('pending');

            $table->char('approved_by_user_id', 36)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['teacher_user_id', 'status'], 'th_teacher_status_idx');
            $table->index(['branch_id', 'status'], 'th_branch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_honors');
    }
};
