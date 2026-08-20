<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Sama seperti 2 migration sebelumnya: tabel `universities` sudah ada live,
| di sini hanya menambah kolom `major_id` (ALTER). Kolom `city` yang sudah
| ada di tabel ini SUDAH berupa relasi ke `cities.id` (lihat form create/edit
| university yang memakai <select> cities) jadi tidak disentuh/diubah.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->char('major_id', 36)->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropColumn('major_id');
        });
    }
};
