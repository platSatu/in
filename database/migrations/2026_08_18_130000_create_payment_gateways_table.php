<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Satu baris = satu konfigurasi gateway (boleh simpan Duitku, Midtrans, dan
     * iPaymu sekaligus supaya kredensialnya tidak hilang saat gonta-ganti),
     * tapi cuma 1 baris yang boleh `is_active = true` dalam satu waktu per user
     * (itu yang dipakai saat transaksi pembayaran dibuat).
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();

            $table->enum('gateway', ['duitku', 'midtrans', 'ipaymu']);
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');

            // Kredensial disimpan sebagai JSON karena tiap gateway butuh field yang beda:
            // - duitku  : merchant_code, secret_key
            // - midtrans: client_key, server_key
            // - ipaymu  : va, api_key
            $table->json('credentials')->nullable();

            $table->boolean('is_active')->default(false);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'gateway']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
