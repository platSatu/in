<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'student.files' (29 Oktober 2026 -- menu Students > File Student,
| lihat config/menu.php & App\Http\Controllers\Student\StudentFileController).
| Pola sama dengan migration sync_permissions_and_grant_superadmin_backup.php.
|
| Role lain: buka halaman Roles -> edit role -> centang "File Student" (grup Students).
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'student.files')->first();

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
