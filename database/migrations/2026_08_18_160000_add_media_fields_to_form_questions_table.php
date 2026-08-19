<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu pertanyaan sekarang bisa berupa kombinasi bebas dari teks, audio,
     * dan/atau gambar (mis. soal Listening: audio + beberapa gambar pilihan,
     * tanpa teks pertanyaan sama sekali). Karena itu question_text yang
     * sebelumnya NOT NULL harus dilonggarkan jadi nullable — validasi "minimal
     * salah satu harus diisi" dipindah ke level aplikasi (FormQuestionController).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('form_questions', 'description')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->text('description')->nullable()->after('question_text');
            });
        }

        if (!Schema::hasColumn('form_questions', 'image')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->string('image')->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('form_questions', 'audio')) {
            Schema::table('form_questions', function (Blueprint $table) {
                $table->string('audio')->nullable()->after('image');
            });
        }

        DB::statement('ALTER TABLE form_questions MODIFY question_text TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE form_questions MODIFY question_text TEXT NOT NULL');

        Schema::table('form_questions', function (Blueprint $table) {
            $columns = array_filter(['audio', 'image', 'description'], fn ($col) => Schema::hasColumn('form_questions', $col));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
