<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progress follow-up siswa (lihat App\Models\Student::PROGRESS_LABELS).
 * Nullable: data lama tetap kosong sampai admin/sales mulai follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('progress_student', 30)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['progress_student']);
            $table->dropColumn('progress_student');
        });
    }
};
