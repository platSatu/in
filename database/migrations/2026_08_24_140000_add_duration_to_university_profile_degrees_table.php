<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tambahan untuk fitur "add row" Degree/Intake di University Profile.
| Kebutuhan tiap kampus beda-beda (mis. Bachelor 4 Years, Master 2.5 Years,
| Language Program 6 Month - 1 Year) jadi kolom ini nullable, satu baris
| Degree/Intake boleh tanpa Duration sama sekali.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            if (!Schema::hasColumn('university_profile_degrees', 'duration')) {
                $table->string('duration')->nullable()->after('intake');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            if (Schema::hasColumn('university_profile_degrees', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};
