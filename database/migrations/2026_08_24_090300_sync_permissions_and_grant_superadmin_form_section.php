<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Sama polanya dengan sync_permissions_and_grant_superadmin.php &
     * sync_permissions_and_grant_superadmin_country.php sebelumnya: config/menu.php
     * baru ditambah 1 entry ('quiz.form-section', lihat CHANGELOG di file itu),
     * jadi tabel permissions disinkronkan ulang di sini supaya modul baru ini
     * langsung terdaftar & bisa di-grant admin lewat halaman Role tanpa perlu
     * jalankan `php artisan permissions:sync` manual dulu. Superadmin yang
     * sudah ada juga langsung diberi akses (can_edit=true) ke permission baru
     * ini, konsisten dengan backfill yang sama untuk modul-modul sebelumnya.
     */
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();

        if ($superadmin) {
            $permission = Permission::where('key', 'quiz.form-section')->first();

            if ($permission) {
                $superadmin->permissions()->syncWithoutDetaching([
                    $permission->id => ['can_edit' => true],
                ]);
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback, sama alasannya dengan migration sync
        // permission sebelumnya: menghapus baris permissions/role_permission
        // di sini berisiko menghapus data yang sudah diedit manual admin.
    }
};
