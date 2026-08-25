<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * section_breakdown: snapshot JSON rincian per Section + per Sub Section
     * (nama, jumlah salah, total soal, verdict lolos/berhenti) pada saat hasil
     * result_mode='section_threshold' pertama kali dihitung -- lihat
     * FrontendController::computeSectionThresholdResult(). Dibekukan
     * (snapshot) di sini SENGAJA, bukan dihitung ulang tiap laporan dibuka --
     * supaya laporan yang sudah jadi tidak ikut berubah kalau soal/section-nya
     * diedit admin belakangan. Nullable: mode lain (auto/manual) tidak
     * mengisi kolom ini sama sekali.
     */
    public function up(): void
    {
        Schema::table('form_results', function (Blueprint $table) {
            if (!Schema::hasColumn('form_results', 'section_breakdown')) {
                $table->json('section_breakdown')->nullable()->after('summary_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_results', function (Blueprint $table) {
            if (Schema::hasColumn('form_results', 'section_breakdown')) {
                $table->dropColumn('section_breakdown');
            }
        });
    }
};
