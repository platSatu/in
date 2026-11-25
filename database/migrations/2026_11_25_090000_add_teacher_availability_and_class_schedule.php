<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ketersediaan Pengajar & Pilih Jadwal (25 November 2026).
 *
 * Prinsipnya: kita mempertemukan jadwal siswa dan pengajar. Jadwal TIDAK
 * dibuat rutin oleh admin -- jadwal terbentuk dari pengajuan siswa.
 *
 * 1. teacher_availabilities -- jam buka pengajar per cabang, diisi admin
 *    baris per baris (Senin 13:00-17:00, Rabu 17:00-20:00, ...). Berlaku
 *    setiap minggu sampai admin mengubahnya. Hanya Senin-Sabtu.
 *
 * 2. class_sessions.class_start_at / class_end_at -- jam kelas yang dipilih
 *    siswa. Lama kelas mengikuti credit (1 credit = 1 jam, 1.5 = 1 jam 30
 *    menit). Pengajuan lama yang belum punya jam kelas tetap memakai
 *    requested_at (lihat ClassSession::scopeHeldBetween()).
 *
 * 3. course_packages.class_quota -- berapa siswa boleh belajar bersama
 *    di 1 kelas (pengajar sama, paket sama, jam mulai sama).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_availabilities')) {
            Schema::create('teacher_availabilities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('branch_id')->constrained('company_branch')->cascadeOnDelete();
                $table->char('teacher_user_id', 36)->index();
                $table->unsignedTinyInteger('day_of_week'); // 1 = Senin ... 6 = Sabtu
                $table->time('start_time');
                $table->time('end_time');
                $table->timestamps();

                $table->index(['teacher_user_id', 'branch_id', 'day_of_week'], 'ta_teacher_branch_day_idx');
            });
        }

        if (! Schema::hasColumn('class_sessions', 'class_start_at')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->dateTime('class_start_at')->nullable()->after('requested_at');
                $table->dateTime('class_end_at')->nullable()->after('class_start_at');

                $table->index(['teacher_user_id', 'class_start_at'], 'cs_teacher_class_start_idx');
            });
        }

        if (! Schema::hasColumn('course_packages', 'class_quota')) {
            Schema::table('course_packages', function (Blueprint $table) {
                $table->unsignedSmallInteger('class_quota')->default(1)->after('credits');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_packages', 'class_quota')) {
            Schema::table('course_packages', fn (Blueprint $table) => $table->dropColumn('class_quota'));
        }

        if (Schema::hasColumn('class_sessions', 'class_start_at')) {
            Schema::table('class_sessions', function (Blueprint $table) {
                $table->dropIndex('cs_teacher_class_start_idx');
                $table->dropColumn(['class_start_at', 'class_end_at']);
            });
        }

        Schema::dropIfExists('teacher_availabilities');
    }
};
