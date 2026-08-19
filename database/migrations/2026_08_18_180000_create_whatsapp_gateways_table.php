<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === WHATSAPP GATEWAY SETTINGS ===
     * Sebelumnya kredensial pengirim WhatsApp (Wablas) hardcode lewat env
     * WABLAS_TOKEN/WABLAS_SECRET dan URL API-nya ditulis langsung di controller.
     * Sekarang admin bisa mengatur sendiri dari menu Settings > WhatsApp Gateway,
     * cukup isi API Host + Token + Secret Key — provider apa pun boleh dipakai
     * selama prosedur pengirimannya sama (POST {api_host}/api/v2/send-message,
     * header Authorization: token.secret — format Wablas-compatible).
     *
     * Satu baris = satu konfigurasi gateway (boleh simpan lebih dari 1 provider),
     * tapi cuma 1 baris yang boleh is_active = true per user (itu yang dipakai
     * saat sistem mengirim pesan WhatsApp).
     *
     * `gateway` sengaja dibuat enum meskipun baru ada 1 opsi ("whatsapp_gateway")
     * supaya gampang ditambah pilihan lain nanti kalau ada provider dengan
     * prosedur pengiriman yang berbeda.
     */
    public function up(): void
    {
        Schema::create('whatsapp_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();

            $table->enum('gateway', ['whatsapp_gateway'])->default('whatsapp_gateway');
            $table->string('name')->nullable();

            $table->string('api_host', 255);
            $table->string('token', 255);
            $table->string('secret_key', 255);

            $table->boolean('is_active')->default(false);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_gateways');
    }
};
