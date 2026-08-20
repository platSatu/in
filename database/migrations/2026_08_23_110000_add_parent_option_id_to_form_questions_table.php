<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pertanyaan bercabang (conditional/nested questions): satu opsi single choice
     * atau multiple choice bisa "memicu" pertanyaan anak yang hanya ditampilkan ke
     * peserta kalau opsi itu dipilih. parent_option_id sengaja menunjuk ke
     * form_question_options.id (BUKAN ke form_questions.id lain) — dengan begini
     * nesting bisa berlapis TANPA BATAS: opsi milik pertanyaan anak pun bisa
     * dijadikan pemicu untuk pertanyaan cucu, dst, tanpa perlu kolom/tabel
     * tambahan (mirip struktur category -> sub category yang berulang).
     *
     * Null berarti pertanyaan utama (root), selalu tampil dari awal.
     */
    public function up(): void
    {
        if (Schema::hasColumn('form_questions', 'parent_option_id')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->char('parent_option_id', 36)->nullable()->after('form_id')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_questions', 'parent_option_id')) {
            return;
        }

        Schema::table('form_questions', function (Blueprint $table) {
            $table->dropColumn('parent_option_id');
        });
    }
};
