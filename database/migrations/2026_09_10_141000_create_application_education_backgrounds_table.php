<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FASE 3 -- baris "Education Background" (fitur "add row") milik 1
| UniversityApplication -- 1-ke-banyak (beda dari application_form_details
| yang 1-ke-1), sesuai instruksi form aslinya: "Start From Elementary
| School, Junior High, Senior High/University" (siswa isi 1 baris per
| jenjang pendidikan, urutannya bebas lewat 'sort_order').
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_education_backgrounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('application_id', 36);

            // elementary | junior_high | senior_high | university -- lihat
            // ApplicationEducationBackground::LEVEL_*.
            $table->string('level')->nullable();
            $table->string('school_name')->nullable();
            $table->string('location')->nullable();
            $table->string('year_start')->nullable();
            $table->string('year_end')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_education_backgrounds');
    }
};
