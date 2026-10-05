<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poin "Why Study Here" per universitas (6 Oktober 2026), tampil di halaman
 * detail kampus. Disimpan satu poin per baris (seperti entry_requirements);
 * kosong = halaman memakai poin default (University::whyStudyPoints()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->text('why_study_here')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropColumn('why_study_here');
        });
    }
};
