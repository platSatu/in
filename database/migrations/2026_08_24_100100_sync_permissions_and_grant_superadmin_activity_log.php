<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Sama polanya dengan sync_permissions_and_grant_superadmin_form_section.php:
     * config/menu.php ditambah 1 entry baru ('activity-log'), jadi tabel
     * permissions disinkronkan ulang di sini supaya modul ini langsung terdaftar
     * & bisa di-grant admin lain lewat halaman Role, dan superadmin yang sudah
     * ada langsung diberi akses.
     */
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();

        if ($superadmin) {
            $permission = Permission::where('key', 'activity-log')->first();

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
