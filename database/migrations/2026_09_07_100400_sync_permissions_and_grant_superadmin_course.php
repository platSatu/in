<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul 'course.type', 'course.class', 'course.level' & 'course.package'
| (lihat config/menu.php, group 'Course') ditambahkan SETELAH migration
| backfill superadmin yang lama sudah jalan, jadi belum otomatis masuk ke
| tabel `permissions` maupun ter-grant ke role superadmin -- pola sama
| persis dengan migration sync_permissions_and_grant_superadmin_zoom.php.
|
| Kalau admin yang dipakai BUKAN role 'superadmin', tinggal buka halaman
| Roles -> edit role tsb -> centang "Course Type"/"Course Class"/
| "Course Level"/"Course Package" (Lihat + Kelola) -> Save.
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();

        if (! $superadmin) {
            return;
        }

        $grants = Permission::whereIn('key', [
                'course.type',
                'course.class',
                'course.level',
                'course.package',
            ])
            ->get()
            ->mapWithKeys(fn (Permission $permission) => [$permission->id => ['can_edit' => true]])
            ->all();

        if (! empty($grants)) {
            $superadmin->permissions()->syncWithoutDetaching($grants);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback -- sama alasannya dengan migration backfill
        // superadmin sebelumnya: menghapus baris di sini berisiko menghapus
        // data yang sudah diedit manual admin lewat halaman Role.
    }
};
