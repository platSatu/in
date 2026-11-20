<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Navigasi nomor soal (opsional) di halaman quiz peserta -- 20 November 2026.
 * Default false: semua form yang sudah berjalan tampil persis seperti sebelumnya
 * sampai admin menyalakan saklarnya di Create/Edit Form.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('forms', 'question_nav_enabled')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->boolean('question_nav_enabled')->default(false)->after('timer_auto_restart');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('forms', 'question_nav_enabled')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('question_nav_enabled');
            });
        }
    }
};
