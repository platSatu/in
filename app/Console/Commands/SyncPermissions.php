<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

/**
 * Sinkronkan tabel permissions dari config/menu.php. Jalankan ulang kapan
 * saja setelah menambah/mengubah entry di config/menu.php (mis. saat modul
 * baru ditambahkan di fase berikutnya) supaya halaman Role bisa langsung
 * menampilkan & memberi akses ke modul itu tanpa migration baru.
 */
class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sinkronkan daftar permission/menu dari config/menu.php ke tabel permissions.';

    public function handle(): int
    {
        Permission::syncFromRegistry();

        $this->info('Permission berhasil disinkronkan dari config/menu.php.');

        return self::SUCCESS;
    }
}
