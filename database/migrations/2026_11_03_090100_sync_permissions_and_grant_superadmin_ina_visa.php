<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'student.ina-visa' (3 November 2026 -- menu Students > Ina Visa,
| lihat config/menu.php & App\Http\Controllers\Student\InaVisaController).
| Pola sama dengan migration sync_permissions_and_grant_superadmin_student_files.php.
|
| Role lain: buka halaman Roles -> edit role -> centang "Ina Visa" (grup Students).
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'student.ina-visa')->first();

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
