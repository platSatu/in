<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom `order` dipakai supaya urutan opsi jawaban yang ditampilkan (di form
     * publik maupun halaman admin) sama persis dengan urutan baris yang diinput
     * admin saat "add rows" di form-question-option/create — sebelumnya opsi
     * tidak punya kolom order sendiri jadi urutannya bergantung ke urutan baris
     * fisik di database (UUID primary key, tidak bisa diandalkan urutannya).
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun kolomnya sudah ada duluan.
        if (Schema::hasColumn('form_question_options', 'order')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('question_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('form_question_options', 'order')) {
            return;
        }

        Schema::table('form_question_options', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
