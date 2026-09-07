<?php

namespace App\Console\Commands;

use App\Models\ZoomSetting;
use Illuminate\Console\Command;

/**
 * Isi 1 baris zoom_settings dari kredensial di file .env (ZOOM_ACCOUNT_ID,
 * ZOOM_CLIENT_ID, ZOOM_CLIENT_SECRET, ZOOM_SECRET_TOKEN -- lihat
 * config/services.php key 'zoom').
 *
 * SENGAJA lewat command ini (bukan ditulis manual lewat migration/seeder)
 * supaya kredensial ASLI tidak pernah perlu ditulis ke file kode yang ikut
 * ke-commit ke git -- cukup taruh sekali di .env (sudah di-.gitignore),
 * lalu jalankan `php artisan zoom:bootstrap-credentials` sekali. Setelah itu
 * kredensial sudah pindah ke database (ter-enkripsi, lihat
 * App\Models\ZoomSetting) dan bisa diubah kapan pun lewat menu
 * Settings > Setting Zoom tanpa perlu sentuh .env atau deploy ulang lagi.
 *
 * Aman dijalankan berkali-kali: baris dengan 'name' yang sama akan di-update
 * (bukan bikin duplikat).
 */
class BootstrapZoomCredentials extends Command
{
    protected $signature = 'zoom:bootstrap-credentials';

    protected $description = 'Salin kredensial Zoom dari .env ke tabel zoom_settings (sekali jalan setelah setup awal)';

    public function handle(): int
    {
        $accountId = config('services.zoom.account_id');
        $clientId = config('services.zoom.client_id');
        $clientSecret = config('services.zoom.client_secret');
        $secretToken = config('services.zoom.secret_token');

        if (empty($accountId) || empty($clientId) || empty($clientSecret)) {
            $this->error('ZOOM_ACCOUNT_ID / ZOOM_CLIENT_ID / ZOOM_CLIENT_SECRET belum diisi di .env. Tidak ada yang disimpan.');

            return self::FAILURE;
        }

        // Nonaktifkan dulu baris lain yang mungkin sedang aktif, supaya baris
        // hasil bootstrap ini yang jadi satu-satunya konfigurasi aktif (pola
        // sama seperti PaymentGatewayController::deactivateOthers()).
        ZoomSetting::query()->update(['is_active' => false]);

        $setting = ZoomSetting::updateOrCreate(
            ['name' => 'Zoom Account (dari .env)'],
            [
                'account_id' => $accountId,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'secret_token' => $secretToken,
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $this->info("Kredensial Zoom berhasil disimpan ke database (id: {$setting->id}) & diaktifkan.");
        $this->line('Selanjutnya kredensial ini bisa dikelola langsung lewat menu Settings > Setting Zoom.');

        return self::SUCCESS;
    }
}
