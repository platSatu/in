<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/*
| Modul baru 'quiz.class-schedule' (lihat config/menu.php) — sama polanya
| dengan migration sync_permissions_and_grant_superadmin_country: modul baru
| tidak otomatis masuk ke tabel `permissions` maupun ter-grant ke role
| superadmin begitu saja, jadi menu "Class Schedule" tidak akan muncul di
| sidebar meski route & view-nya sudah ada sampai migration ini dijalankan.
|
| Migration ini:
| 1) Sync ulang config/menu.php -> tabel permissions (Permission::syncFromRegistry()
|    idempoten, aman dijalankan berkali-kali).
| 2) Grant KHUSUS permission 'quiz.class-schedule' (can_edit=true) ke role
|    superadmin saja (syncWithoutDetaching supaya tidak menimpa permission
|    role lain).
|
| Kalau admin yang dipakai BUKAN role 'superadmin', tinggal buka halaman
| Roles -> edit role tsb -> centang "Class Schedule" (Lihat + Kelola) -> Save.
*/
return new class extends Migration
{
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();
        $classSchedule = Permission::where('key', 'quiz.class-schedule')->first();

        if ($superadmin && $classSchedule) {
            $superadmin->permissions()->syncWithoutDetaching([
                $classSchedule->id => ['can_edit' => true],
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback, sama alasannya dengan migration sync
        // permission sebelumnya: menghapus baris di sini berisiko menghapus
        // data yang sudah diedit manual admin lewat halaman Role.
    }
};
