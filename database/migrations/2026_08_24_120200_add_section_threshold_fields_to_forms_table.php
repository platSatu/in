<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ambang batas (threshold) untuk mode hasil "section_threshold" —
     * penilaian gaya HSK: peserta "lolos" dari satu Section (lanjut ke
     * Section berikutnya) atau "berhenti" di Section itu (jadi hasil akhir),
     * ditentukan dari jumlah jawaban SALAH di Sub Section-Sub Section milik
     * Section tsb. Lihat FrontendController::computeSectionThresholdResult()
     * untuk algoritma lengkapnya.
     *
     * Sengaja 1 pasang kolom per FORM (bukan per Section) — sesuai kebutuhan
     * saat ini ambang batas berlaku SERAGAM ke semua Section dalam 1 form,
     * dan supaya bisa dipakai ulang oleh jenis quiz lain di luar HSK tanpa
     * perlu skema baru. Nullable, TIDAK diberi default di level database
     * (default 3/1 diterapkan di kode — FormController::store()/update() —
     * hanya waktu admin benar-benar mengaktifkan mode ini) supaya form yang
     * tidak memakai mode ini kolomnya tetap NULL apa adanya.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->unsignedTinyInteger('section_fail_threshold')->nullable()->after('result_mode');
            $table->unsignedTinyInteger('section_pass_threshold')->nullable()->after('section_fail_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['section_fail_threshold', 'section_pass_threshold']);
        });
    }
};
