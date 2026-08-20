<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabel anak baru untuk Degree + Intake per University Profile, dipakai oleh
| fitur "add row" (bisa lebih dari satu kombinasi degree/intake dalam 1
| profile, mis. "Bachelor - September" & "Master - March"). Pola & penamaan
| kolom sengaja disamakan dengan `university_album_photos` (child table lain
| yang juga dipakai lewat fitur "add row").
|
| Kolom `degree`/`intake` lama yang masih ada langsung di tabel
| `university_profiles` SENGAJA TIDAK disentuh/dihapus di sini — supaya data
| lama & fitur edit yang belum diubah tetap jalan seperti biasa.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_profile_degrees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('university_profile_id', 36)->index();
            $table->char('user_id', 36)->nullable()->index();
            $table->string('degree')->nullable();
            $table->string('intake')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_profile_degrees');
    }
};
