<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (15 September 2026, permintaan user -- "data yang ditampilkan
     * untuk student/sales/pengajar/superadmin"): Academic Calendar SEBELUMNYA
     * cuma "kalender pribadi" (DashboardController::index() lama memfilter
     * `where user_id = user yang login`, jadi staff/siswa yang tidak pernah
     * BUAT kalender sendiri pasti selalu kosong). Sekarang jadi "kalender
     * bersama" yang di-broadcast admin: tiap entry BOLEH di-assign ke 1 role
     * tertentu dan/atau 1 branch tertentu lewat 2 kolom baru ini.
     *
     * - target_role_id  NULL = tampil untuk SEMUA role.
     * - target_branch_id NULL = tampil untuk SEMUA branch.
     * - Kalau keduanya diisi, HARUS keduanya cocok (role user ini salah
     *   satunya = target_role_id, DAN branch user ini = target_branch_id)
     *   baru entry itu muncul di dashboard user tsb -- lihat
     *   DashboardController::index() untuk query lengkapnya.
     * - Superadmin (scope_level company) TIDAK terpengaruh sama sekali oleh
     *   kolom ini, tetap selalu lihat SEMUA entry apa pun target-nya.
     *
     * Sengaja char(36) + index biasa (BUKAN foreign key/constrained()) --
     * mengikuti pola kolom uuid "referensi lepas" yang sudah dipakai di
     * migration add_branch_and_form_to_students_table (branch_id/form_id) &
     * add_scope_to_students_table (company_division_id), supaya konsisten
     * dengan gaya migration lain di project ini.
     */
    public function up(): void
    {
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->char('target_role_id', 36)->nullable()->after('user_id');
            $table->char('target_branch_id', 36)->nullable()->after('target_role_id');

            $table->index('target_role_id');
            $table->index('target_branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->dropIndex(['target_role_id']);
            $table->dropIndex(['target_branch_id']);
            $table->dropColumn(['target_role_id', 'target_branch_id']);
        });
    }
};
