<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === ZOOM SETTINGS ===
     * Kredensial Server-to-Server OAuth Zoom (Account ID, Client ID, Client
     * Secret, Secret Token webhook) diatur admin lewat menu Settings > Setting
     * Zoom, dipakai App\Services\Zoom\ZoomClient untuk membuat/mengubah/
     * mengakhiri meeting & mengambil rekaman lewat Zoom API.
     *
     * Satu baris = satu app Zoom (boleh simpan lebih dari 1, mis. sandbox &
     * production), tapi cuma 1 baris yang boleh is_active = true dalam satu
     * waktu (itu yang dipakai ZoomClient) -- pola sama persis dengan
     * payment_gateways & whatsapp_gateways.
     *
     * account_id/client_id/client_secret/secret_token disimpan TER-ENKRIPSI
     * (cast 'encrypted' di App\Models\ZoomSetting, makanya kolomnya text bukan
     * string(255) -- ciphertext-nya jauh lebih panjang dari plaintext-nya)
     * karena kredensial ini memberi akses penuh membuat/mengubah/menghapus
     * meeting apa pun di akun Zoom terkait.
     */
    public function up(): void
    {
        Schema::create('zoom_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();

            $table->string('name')->nullable();

            $table->text('account_id');
            $table->text('client_id');
            $table->text('client_secret');
            // Secret Token dipakai untuk verifikasi signature Webhook Zoom
            // (header x-zm-signature) -- belum dipakai di fitur ini, disimpan
            // sekalian supaya siap dipakai kalau webhook diaktifkan nanti
            // (mis. notifikasi otomatis begitu recording selesai diproses).
            $table->text('secret_token')->nullable();

            $table->boolean('is_active')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_settings');
    }
};
