<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'settings.backup' (29 Oktober 2026 -- menu Settings > Backup Database,
| lihat config/menu.php & App\Http\Controllers\Settings\DatabaseBackupController).
| Pola sama dengan migration sync_permissions_and_grant_superadmin_course_report.php:
| daftarkan ke tabel `permissions`, lalu grant ke role 'superadmin' (lihat + kelola).
|
| Role lain: buka halaman Roles -> edit role -> centang "Backup Database" (grup Settings).
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'settings.backup')->first();

        if ($superadmin && $permission) {
            $superadmin->permissions()->syncWithoutDetaching([
                $permission->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback, sama alasannya dengan migration sync permission sebelumnya.
    }
};
