<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu kedaluwarsa transaksi ini, diisi sekali saat FormPayment dibuat
     * (now() + payment_gateways.expiry_minutes milik gateway yang dipakai —
     * lihat FormPaymentController::init()). Dipakai untuk:
     * 1. Countdown yang ditampilkan ke peserta di step Payment (wizard publik).
     * 2. Penanda "expired" (biar sinkron dengan dashboard gateway aslinya,
     *    mis. Duitku) — status hanya berubah 'pending' -> 'expired' lewat
     *    UPDATE ber-syarat (WHERE status='pending'), baik saat wizard polling
     *    status (self-heal, lihat FormPaymentController::status()) maupun
     *    lewat scheduled command payments:expire-stale sebagai jaring pengaman.
     *
     * Index gabungan (status, expires_at) supaya query sweep command di atas
     * tidak full-scan tabel ini kalau datanya sudah besar.
     */
    public function up(): void
    {
        Schema::table('form_payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('form_payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
