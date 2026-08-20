<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan untuk 2026_08_21_090300_create_role_permission_table:
     * migration itu sempat membuat kolom 'id' UUID primary key tanpa default
     * di level DB. Baris role_permission ditulis lewat
     * $role->permissions()->sync(...) (RoleController::syncPermissions),
     * yang melakukan bulk insert via query builder — TIDAK memicu event
     * 'creating' Eloquent, jadi kolom id ber-trait HasUuids itu tidak pernah
     * keisi otomatis di situ, dan insert-nya gagal dengan error
     * "Field 'id' doesn't have a default value".
     *
     * Fix: hapus kolom id, pakai primary key komposit (role_id, permission_id)
     * — pola standar Laravel untuk pivot table yang diakses lewat sync()/attach().
     * Kolom id di sini adalah SATU-SATUNYA kolom primary key tabel ini, jadi
     * saat kolomnya di-drop, MySQL otomatis ikut menghapus constraint PRIMARY
     * KEY-nya juga (tidak perlu dropPrimary() eksplisit).
     *
     * Migration timestamp-nya sengaja diletakkan SEBELUM
     * 2026_08_21_090600_sync_permissions_and_grant_superadmin supaya
     * tabelnya sudah benar duluan saat migration itu (yang gagal & otomatis
     * di-rollback sebelumnya, jadi belum tercatat selesai di tabel migrations)
     * dicoba lagi oleh `php artisan migrate`.
     *
     * === GUARD (ditambahkan belakangan) ===
     * 2026_08_21_090300_create_role_permission_table SUDAH diperbaiki
     * langsung di file-nya (sekarang bikin primary key komposit sejak awal,
     * TANPA kolom 'id' & TANPA index 'role_permission_unique') — jadi di
     * database yang baru sama sekali (mis. instalasi/deploy baru, database
     * fresh migrate), migration create_role_permission_table di atas sudah
     * bikin tabelnya benar sejak awal, dan migration "fix" ini tidak ada lagi
     * yang perlu diperbaiki. Tanpa guard ini, up() di bawah akan gagal dengan
     * "Can't DROP INDEX role_permission_unique; check that it exists" karena
     * mencoba men-drop kolom/index yang memang tidak pernah dibuat.
     * Migration ini TETAP dipertahankan (bukan dihapus) supaya environment
     * LAMA yang tabelnya masih dalam kondisi buggy (kolom 'id' + index
     * role_permission_unique) tetap bisa diperbaiki seperti semula.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('role_permission', 'id')) {
            return;
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropUnique('role_permission_unique');
            $table->dropColumn('id');
        });

        Schema::table('role_permission', function (Blueprint $table) {
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('role_permission', 'id')) {
            return;
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropPrimary(['role_id', 'permission_id']);
        });

        Schema::table('role_permission', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
            $table->unique(['role_id', 'permission_id'], 'role_permission_unique');
        });
    }
};
