<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai satu pertanyaan itu bagian dari step "Data Pribadi" (mis. sekolah,
     * kelas) atau "Placement Test" (soal yang dinilai). Default 'placement_test'
     * supaya semua pertanyaan yang sudah ada sekarang tetap tampil di step yang
     * sama seperti sebelumnya (tidak ada perubahan perilaku untuk form lama).
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun kolomnya sudah ada duluan.
        if (Schema::hasColumn('form_questions', 'stage_group')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->enum('stage_group', ['personal_data', 'placement_test'])
                ->default('placement_test')
                ->after('form_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_questions', 'stage_group')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->dropColumn('stage_group');
        });
    }
};
