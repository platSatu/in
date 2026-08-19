<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Durasi (menit) sebelum transaksi pembayaran yang dibuat lewat gateway ini
     * otomatis dianggap kedaluwarsa kalau belum dibayar. Sebelumnya nilai ini
     * hardcode 60 menit khusus di DuitkuGateway (parameter expiryPeriod) —
     * sekarang dipindah jadi setting per gateway (admin bisa ubah lewat
     * Settings > Payment Gateway) dan dipakai general untuk SEMUA gateway
     * (Duitku/Midtrans/iPaymu) sebagai acuan form_payments.expires_at.
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unsignedSmallInteger('expiry_minutes')->default(60);
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('expiry_minutes');
        });
    }
};
