<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE TYPE ===
| Master data format kelas kursus, mis. "Private" / "Semi-Private" (lihat
| brosur INA YULE Chinese Language Center). Dirujuk oleh `course_packages`
| lewat kolom `course_type_id`, dan jadi sumbu utama alur konversi kredit
| antar class type (mis. Semi-Private -> Private).
|
| Pola kolom (uuid id, user_id nullable, name, description, status) sengaja
| disamakan dengan `countries`/`cities` supaya konsisten dengan modul master
| data lain di aplikasi ini (pakai App\Helpers\AdminCrud).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable()->index();
            $table->string('name');
            // Unique supaya tidak ada 2 baris "Private" yang beda-beda -- data
            // ini jadi acuan dropdown di form Course Package, duplikat cuma
            // akan membingungkan admin saat memilih.
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_type');
    }
};
