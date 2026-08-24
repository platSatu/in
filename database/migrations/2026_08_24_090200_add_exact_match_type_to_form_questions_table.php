<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipe pertanyaan baru 'exact_match': peserta harus mengetik jawaban PERSIS
     * sama (case-sensitive, cuma spasi di awal/akhir yang diabaikan — lihat
     * FrontendController::saveSingleQuestionAnswer()) dengan `correct_answer`.
     * Dibuat untuk soal yang jawabannya harus dituliskan ulang persis, mis.
     * karakter Mandarin, bukan dipilih dari opsi.
     *
     * `match_score` = poin yang didapat peserta kalau jawabannya cocok persis,
     * dipakai untuk form dengan result_mode='auto' — polanya sama dengan
     * form_question_options.score untuk pertanyaan single/multiple choice,
     * cuma di sini disimpan langsung di pertanyaannya karena tipe ini tidak
     * punya form_question_options sama sekali.
     *
     * Kolom `type` adalah ENUM asli di MySQL (bukan varchar bebas) — mengikuti
     * pola migration add_required_and_expand_type_to_form_questions_table &
     * add_file_type_to_form_questions_table sebelumnya: nilai lama TETAP ada,
     * cuma ditambah satu nilai baru di akhir daftar.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major','file','exact_match') NOT NULL");

        if (!Schema::hasColumn('form_questions', 'correct_answer')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->string('correct_answer')->nullable()->after('type');
            });
        }

        if (!Schema::hasColumn('form_questions', 'match_score')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->integer('match_score')->nullable()->after('correct_answer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_questions', function (Blueprint $table) {
            $columns = array_filter(['match_score', 'correct_answer'], fn ($col) => Schema::hasColumn('form_questions', $col));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        DB::statement("ALTER TABLE form_questions MODIFY type ENUM('text','textarea','number','date','single_choice','multiple_choice','dropdown','major','file') NOT NULL");
    }
};
