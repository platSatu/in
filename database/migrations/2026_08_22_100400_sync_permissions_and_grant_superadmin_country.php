<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul 'country' (lihat config/menu.php) ditambahkan SETELAH migration
| backfill superadmin yang lama (2026_08_21_090600_sync_permissions_and_grant_superadmin)
| sudah jalan, jadi belum otomatis masuk ke tabel `permissions` maupun
| ter-grant ke role superadmin — makanya menu "Country" belum muncul di
| sidebar meski route & view-nya sudah ada (lihat App\Concerns\HasScopedAccess::
| canAccessPermission(), yang murni cek data di role_permission, tanpa bypass
| untuk superadmin).
|
| Migration ini:
| 1) Sync ulang config/menu.php -> tabel permissions (Permission::syncFromRegistry()
|    idempoten via updateOrCreate berdasar 'key', aman dijalankan berkali-kali,
|    tidak mengubah key yang sudah ada).
| 2) Grant KHUSUS permission 'country' (can_edit=true) ke role superadmin saja
|    (pakai syncWithoutDetaching supaya tidak menimpa/menghapus permission
|    role lain yang mungkin sudah diatur manual lewat halaman Role).
|
| Kalau admin yang dipakai BUKAN role 'superadmin', tinggal buka halaman
| Roles -> edit role tsb -> centang "Country" (Lihat + Kelola) -> Save.
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $country = Permission::where('key', 'country')->first();

        if ($superadmin && $country) {
            $superadmin->permissions()->syncWithoutDetaching([
                $country->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback, sama alasannya dengan migration backfill
        // superadmin sebelumnya: menghapus baris di sini berisiko menghapus
        // data yang sudah diedit manual admin lewat halaman Role.
    }
};
