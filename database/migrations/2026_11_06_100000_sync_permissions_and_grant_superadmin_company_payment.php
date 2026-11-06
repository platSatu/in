<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'company.payment' (6 November 2026 -- menu Company > Payment, lihat config/menu.php &
| App\Http\Controllers\Company\CompanyPaymentController). Pola sama dengan migration
| sync_permissions_and_grant_superadmin_ina_visa.php.
|
| Route-nya juga dijaga role:superadmin, jadi role lain tetap tidak bisa membuka walau dicentang.
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $permission = Permission::where('key', 'company.payment')->first();

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
