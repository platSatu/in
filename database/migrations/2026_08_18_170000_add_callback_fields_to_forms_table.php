<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === CALLBACK LINK ===
     * Fitur "callback": admin bisa menempelkan sebuah link (mis. link Zoom) ke sebuah
     * form. Link ini hanya boleh diperlihatkan ke peserta lewat FrontendController
     * setelah submit selesai, dan (kalau form requires_payment) setelah status
     * pembayarannya sudah diverifikasi "paid" oleh webhook resmi gateway. Toggle ini
     * SENGAJA dibuat berdiri sendiri (tidak bergantung ke requires_payment) supaya
     * form gratis pun bisa dipakai untuk kasus ini (link langsung tampil setelah
     * submit sukses, tanpa gerbang pembayaran).
     *
     * - is_callback_enabled: toggle admin, default false supaya form lama tidak
     *   berubah perilaku.
     * - callback_link: link nullable yang diisi admin, hanya dipakai kalau
     *   is_callback_enabled true.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->boolean('is_callback_enabled')->default(false)->after('payment_position');
            $table->string('callback_link', 500)->nullable()->after('is_callback_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['is_callback_enabled', 'callback_link']);
        });
    }
};
