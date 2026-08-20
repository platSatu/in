<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * 1) Isi tabel permissions dari daftar menu di config/menu.php (sumber
     *    yang sama dipakai sidebar & PermissionMiddleware). Idempoten — aman
     *    dijalankan ulang lewat `php artisan permissions:sync` kalau nanti
     *    config/menu.php ditambah entry baru di fase berikutnya.
     * 2) Backfill SATU KALI: grant seluruh permission (can_edit=true) ke role
     *    'superadmin' yang sudah ada, supaya user superadmin yang sudah ada
     *    tidak kehilangan akses begitu routes/web.php berpindah dari
     *    middleware 'role:superadmin' ke 'permission:<key>'. Ini backfill
     *    historis khusus role 'superadmin' — BUKAN aturan umum, role baru
     *    manapun (termasuk role scope_level=company lain) tidak otomatis
     *    dapat semua permission, harus di-centang manual di form Role.
     */
    public function up(): void
    {
        Permission::syncFromRegistry();

        $superadmin = Role::where('slug', 'superadmin')->first();

        if ($superadmin) {
            $grants = Permission::pluck('id')
                ->mapWithKeys(fn ($id) => [$id => ['can_edit' => true]]);

            $superadmin->permissions()->syncWithoutDetaching($grants);
        }
    }

    public function down(): void
    {
        // Sengaja tidak dirollback: menghapus baris permissions/role_permission
        // di sini berisiko menghapus data yang sudah diedit manual oleh admin
        // lewat halaman Role setelah migration ini jalan. Struktur tabelnya
        // sendiri sudah di-drop oleh migration create_permissions_table &
        // create_role_permission_table saat rollback berurutan.
    }
};
