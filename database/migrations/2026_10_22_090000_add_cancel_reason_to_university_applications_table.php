<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alasan admin membatalkan Aplikasi Kuliah (22 Oktober 2026). Siapa & kapan
 * sudah tercatat otomatis di Activity Log, jadi cukup alasannya di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
