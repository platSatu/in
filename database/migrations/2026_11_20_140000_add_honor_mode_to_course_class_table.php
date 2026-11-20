<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cara hitung honor per Course Class (20 November 2026):
 * per_class = cara yang sudah berjalan (default, semua kelas lama tetap begini);
 * per_hour  = teacher_fee x total jam mengajar (1 credit disetujui = 1 jam).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_class', 'honor_mode')) {
            Schema::table('course_class', function (Blueprint $table) {
                $table->string('honor_mode', 20)->default('per_class')->after('teacher_fee');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_class', 'honor_mode')) {
            Schema::table('course_class', fn (Blueprint $table) => $table->dropColumn('honor_mode'));
        }
    }
};
