<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_other` menandai satu opsi jawaban Multiple Choice sebagai opsi
     * "Lainnya" (isian bebas) — begitu peserta quiz mencentang opsi ini,
     * halaman publik menampilkan kolom teks tambahan untuk mereka ketik
     * sendiri jawabannya, dan teks itu disimpan di FormAnswer.answer_text
     * (lihat FrontendController::saveQuestionAnswers()). Default false supaya
     * opsi-opsi lama tetap berperilaku seperti biasa.
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun kolomnya sudah ada duluan (pola yang
        // sama dengan migration add_order_to_form_question_options_table).
        if (Schema::hasColumn('form_question_options', 'is_other')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->boolean('is_other')->default(false)->after('score');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_question_options', 'is_other')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->dropColumn('is_other');
        });
    }
};
