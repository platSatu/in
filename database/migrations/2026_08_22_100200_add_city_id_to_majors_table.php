<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Sama seperti migration `country_id` di `cities`: tabel `majors` sudah ada
| live, di sini hanya menambah kolom `city_id` (ALTER).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->char('city_id', 36)->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }
};
