<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Timer untuk step Placement Test (soal-soal) di wizard publik.
     *
     * - timer_enabled: gerbang utama. Kalau mati, 3 kolom lain diabaikan total
     *   (baik oleh JS di form-wizard.blade.php maupun kalau ada yang iseng
     *   POST langsung ke endpoint timeout-save).
     * - timer_duration_minutes: durasi hitungan mundur, cuma dipakai kalau
     *   timer_enabled aktif.
     * - timer_auto_save: begitu waktu habis, jawaban yang sempat terisi
     *   (berapa pun jumlahnya) disimpan sebagai FormSubmission dengan
     *   is_timeout_partial=true (lihat migration form_submissions).
     * - timer_auto_restart: begitu waktu habis, step Placement Test direset
     *   ke soal pertama (di sisi JS, tanpa reload halaman penuh — supaya
     *   tidak memicu ulang alur pembayaran/data pribadi dan tidak menambah
     *   view_count form). Kalau timer_auto_save juga aktif, urutannya:
     *   simpan dulu, baru direset.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->boolean('timer_enabled')->default(false);
            $table->unsignedSmallInteger('timer_duration_minutes')->nullable();
            $table->boolean('timer_auto_save')->default(false);
            $table->boolean('timer_auto_restart')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['timer_enabled', 'timer_duration_minutes', 'timer_auto_save', 'timer_auto_restart']);
        });
    }
};
