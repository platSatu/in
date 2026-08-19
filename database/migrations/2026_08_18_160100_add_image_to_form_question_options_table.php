<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opsi jawaban sekarang bisa berupa gambar tanpa teks sama sekali (mis.
     * soal Listening yang jawabannya 3 gambar A/B/C). option_text yang
     * sebelumnya NOT NULL dilonggarkan jadi nullable — validasi "option_text
     * atau image minimal salah satu diisi" ada di FormQuestionOptionController.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('form_question_options', 'image')) {
            Schema::table('form_question_options', function (Blueprint $table) {
                $table->string('image')->nullable()->after('option_text');
            });
        }

        DB::statement('ALTER TABLE form_question_options MODIFY option_text VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE form_question_options MODIFY option_text VARCHAR(255) NOT NULL DEFAULT ''");

        if (Schema::hasColumn('form_question_options', 'image')) {
            Schema::table('form_question_options', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
