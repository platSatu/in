<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul 'settings.zoom' & 'zoom.meeting' (lihat config/menu.php, group
| 'Zoom') ditambahkan SETELAH migration backfill superadmin yang lama sudah
| jalan, jadi belum otomatis masuk ke tabel `permissions` maupun ter-grant ke
| role superadmin -- pola sama persis dengan migration
| sync_permissions_and_grant_superadmin_country.php dkk.
|
| Kalau admin yang dipakai BUKAN role 'superadmin', tinggal buka halaman
| Roles -> edit role tsb -> centang "Setting Zoom"/"Kelola Meeting"
| (Lihat + Kelola) -> Save.
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

        $grants = Permission::whereIn('key', ['settings.zoom', 'zoom.meeting'])
            ->get()
            ->mapWithKeys(fn (Permission $permission) => [$permission->id => ['can_edit' => true]])
            ->all();

        if (! empty($grants)) {
            $superadmin->permissions()->syncWithoutDetaching($grants);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback, sama alasannya dengan migration backfill
        // superadmin sebelumnya: menghapus baris di sini berisiko menghapus
        // data yang sudah diedit manual admin lewat halaman Role.
    }
};
