<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_correct` menandai satu opsi jawaban sebagai jawaban yang BENAR,
     * dipakai oleh mode hasil "section_threshold" (lihat migration
     * add_section_threshold_fields_to_forms_table &
     * FrontendController::computeSectionThresholdResult()) untuk menghitung
     * berapa banyak pertanyaan yang dijawab SALAH per Sub Section.
     *
     * Sengaja kolom TERPISAH dari `score` yang sudah ada (dipakai mode hasil
     * "auto") — `score` itu nilai bebas yang dijumlah admin sesuka hati,
     * sedangkan `is_correct` murni penanda benar/salah biner. Menggabungkan
     * keduanya (mis. "score > 0 berarti benar") akan ambigu untuk form yang
     * sudah lama pakai `score` dengan cara lain. Default false supaya semua
     * opsi lama otomatis dianggap "belum ditandai benar" — TIDAK memengaruhi
     * form manapun yang belum memakai mode hasil section_threshold sama
     * sekali (kolom ini tidak pernah dibaca kalau result_mode form bukan
     * 'section_threshold').
     */
    public function up(): void
    {
        if (Schema::hasColumn('form_question_options', 'is_correct')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->boolean('is_correct')->default(false)->after('score');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_question_options', 'is_correct')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->dropColumn('is_correct');
        });
    }
};
