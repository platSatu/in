<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabel bantu buat generate nomor aplikasi kuliah (format APP-YYMM-00001)
| dengan aman walau ada beberapa submit bersamaan -- satu baris per
| periode (YYMM), nomor urut terakhir disimpan & di-increment pakai
| lockForUpdate() di service generator-nya (lihat fase 4).
|
| Bagian dari fitur Aplikasi Kuliah (Apply ke kampus) -- tabel BARU,
| tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_number_counters', function (Blueprint $table) {
            $table->id();
            $table->string('period_key', 10)->unique(); // contoh: "2609" (Sep 2026)
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_number_counters');
    }
};
