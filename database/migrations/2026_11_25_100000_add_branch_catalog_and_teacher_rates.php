<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog InaYule per cabang & Tarif Pengajar (25 November 2026).
 *
 * 1. course_packages.branch_id -- setiap paket milik 1 cabang (MOI, Pluit,
 *    Online, ...). Paket lama dibiarkan kosong ("Belum ada cabang") dan tidak
 *    tampil di website sampai admin memindahkannya lewat Pilih Semua ->
 *    Pindahkan ke cabang di menu Course Package.
 *
 * 2. company_branch.placement_test_url -- link quiz Placement Test milik
 *    cabang itu, ditempel admin dari menu Quiz.
 *
 * 3. teacher_rates -- Tarif Pengajar: aturan fee honor. Pengajar, cabang, dan
 *    course class boleh kosong (= semua). Aturan paling spesifik yang dipakai,
 *    lihat App\Models\TeacherRate::bestFor(). Tidak ada aturan yang cocok =
 *    fee umum di Course Class seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_packages', 'branch_id')) {
            Schema::table('course_packages', function (Blueprint $table) {
                $table->foreignUuid('branch_id')->nullable()->after('user_id')
                    ->constrained('company_branch')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('company_branch', 'placement_test_url')) {
            Schema::table('company_branch', function (Blueprint $table) {
                $table->string('placement_test_url', 500)->nullable()->after('email');
            });
        }

        if (! Schema::hasTable('teacher_rates')) {
            Schema::create('teacher_rates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('teacher_user_id', 36)->nullable()->index();
                $table->foreignUuid('branch_id')->nullable()->constrained('company_branch')->cascadeOnDelete();
                $table->foreignUuid('course_class_id')->nullable()->constrained('course_class')->cascadeOnDelete();
                $table->string('honor_mode', 20)->default('per_class'); // per_class | per_hour
                $table->decimal('fee', 14, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_rates');

        if (Schema::hasColumn('company_branch', 'placement_test_url')) {
            Schema::table('company_branch', fn (Blueprint $table) => $table->dropColumn('placement_test_url'));
        }

        if (Schema::hasColumn('course_packages', 'branch_id')) {
            Schema::table('course_packages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
