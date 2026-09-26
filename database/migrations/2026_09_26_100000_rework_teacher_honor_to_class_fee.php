<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Honor Pengajar versi baru (26 September 2026, hasil diskusi owner):
 *
 * - Fee pengajar dalam RUPIAH per kelas, diisi superadmin di Course Class
 *   (course_class.teacher_fee). Tidak ada persentase sama sekali --
 *   teacher_commission_rates dihapus total.
 * - "1 kelas = 1 credit": setiap paket yang dibeli (course_package_purchases)
 *   yang credit-nya terpakai bersama seorang pengajar dalam satu periode
 *   dihitung 1 kelas, berapa kali pun dipakai & berapa pun isinya.
 * - Periode (cut-off) dibuat manual per cabang: teacher_honor_periods.
 * - teacher_honors sekarang 1 baris = 1 pengajar dalam 1 periode, dibuat
 *   saat periode ditutup (rekap dikunci di kolom `classes`), lalu
 *   pending -> approved_for_payout -> paid seperti sebelumnya.
 *
 * Honor lama (per sesi, berbasis persen) SENGAJA dihapus (keputusan owner).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_class', 'teacher_fee')) {
            Schema::table('course_class', function (Blueprint $table) {
                $table->decimal('teacher_fee', 14, 2)->default(0)->after('description');
            });
        }

        Schema::dropIfExists('teacher_honors');
        Schema::dropIfExists('teacher_commission_rates');

        Schema::create('teacher_honor_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('company_branch')->restrictOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open'); // open | closed
            $table->char('created_by_user_id', 36)->nullable();
            $table->char('closed_by_user_id', 36)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'start_date'], 'thp_branch_start_idx');
        });

        Schema::create('teacher_honors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('teacher_honor_period_id')->constrained('teacher_honor_periods')->cascadeOnDelete();
            $table->char('teacher_user_id', 36)->index();
            $table->unsignedInteger('class_count');
            $table->decimal('honor_amount', 14, 2);
            $table->json('classes'); // rekap per kelas yang dikunci saat periode ditutup
            $table->string('status', 30)->default('pending');
            $table->char('approved_by_user_id', 36)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_honor_period_id', 'teacher_user_id'], 'th_period_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_honors');
        Schema::dropIfExists('teacher_honor_periods');

        if (Schema::hasColumn('course_class', 'teacher_fee')) {
            Schema::table('course_class', fn (Blueprint $table) => $table->dropColumn('teacher_fee'));
        }
    }
};
