<?php

namespace App\Console\Commands;

use App\Models\FormPayment;
use Illuminate\Console\Command;

/**
 * Jaring pengaman untuk timer pembayaran (lihat form_payments.expires_at).
 *
 * Jalur UTAMA penandaan "expired" sebenarnya sudah self-heal setiap kali wizard
 * publik polling status pembayaran (lihat FormPaymentController::status()) — command
 * ini cuma menangkap transaksi yang browsernya ditutup sebelum sempat di-poll lagi,
 * supaya statusnya tetap benar di dashboard admin walau tidak ada satupun wizard
 * yang masih terbuka untuk transaksi itu.
 *
 * Pakai UPDATE ber-syarat (bukan select-then-save per baris) supaya atomik dan aman
 * dari race condition dengan webhook gateway yang mungkin baru saja menandai baris
 * yang sama "paid" — WHERE status='pending' memastikan baris yang statusnya sudah
 * berubah TIDAK ikut ter-update di sini.
 *
 * Perlu terdaftar di scheduler (lihat bootstrap/app.php -> withSchedule()) DAN
 * cron `* * * * * php artisan schedule:run` benar-benar berjalan di server —
 * kalau belum ada, jalur self-heal di status() saja sudah cukup untuk transaksi
 * yang wizard-nya masih dibuka user, command ini murni pelengkap.
 */
class ExpireStalePayments extends Command
{
    protected $signature = 'payments:expire-stale';

    protected $description = 'Tandai transaksi pembayaran pending yang sudah lewat batas waktu (expires_at) jadi expired.';

    public function handle(): int
    {
        $affected = FormPayment::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        if ($affected > 0) {
            $this->info("{$affected} transaksi pembayaran ditandai expired.");
        }

        return self::SUCCESS;
    }
}
