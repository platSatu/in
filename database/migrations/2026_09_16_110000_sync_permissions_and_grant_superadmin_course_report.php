<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'course.report' (16 September 2026, permintaan user -- menu
| "Laporan" di dalam Course, lihat config/menu.php & App\Http\Controllers\
| Course\CourseReportController) ditambahkan SETELAH migration backfill
| superadmin course yang lama sudah jalan, jadi belum otomatis masuk ke
| tabel `permissions` maupun ter-grant ke role superadmin -- pola sama
| persis dengan migration sync_permissions_and_grant_superadmin_course.php.
|
| Kalau admin yang dipakai BUKAN role 'superadmin', tinggal buka halaman
| Roles -> edit role tsb -> centang "Laporan" (di grup Course) -> Save.
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

        $permission = Permission::where('key', 'course.report')->first();

        if ($permission) {
            $superadmin->permissions()->syncWithoutDetaching([
                $permission->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback -- sama alasannya dengan migration backfill
        // superadmin sebelumnya: menghapus baris di sini berisiko menghapus
        // data yang sudah diedit manual admin lewat halaman Role.
    }
};
