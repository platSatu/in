<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| 3 kolom baru di university_profiles, hasil perbandingan dengan brosur
| kampus (mis. NUAA): Degree Title (gelar akademik, beda dari nama Major
| -- contoh Major "Aeronautical Engineering" gelarnya "Aircraft Design and
| Engineering"), Key Courses (daftar mata kuliah, teks bebas/multi-baris),
| dan Entry Requirements (syarat masuk, teks bebas/multi-baris).
|
| Semua nullable karena kebutuhan tiap kampus beda-beda -- tidak semua
| profile perlu diisi ketiganya.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('university_profiles', 'degree_title')) {
                $table->string('degree_title')->nullable()->after('field');
            }

            if (!Schema::hasColumn('university_profiles', 'key_courses')) {
                $table->text('key_courses')->nullable()->after('degree_title');
            }

            if (!Schema::hasColumn('university_profiles', 'entry_requirements')) {
                $table->text('entry_requirements')->nullable()->after('key_courses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_profiles', function (Blueprint $table) {
            foreach (['degree_title', 'key_courses', 'entry_requirements'] as $column) {
                if (Schema::hasColumn('university_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
