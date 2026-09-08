<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Daftar jenis dokumen yang bisa diupload calon siswa per Aplikasi Kuliah
| (Passport, Pass Photo, Transcript, dst) -- data konfigurasi, diseed lewat
| DocumentTypeSeeder, bisa admin tambah/ubah lewat menu setting nanti.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code')->unique(); // contoh: 'passport', 'pass_photo'
            $table->string('label');
            $table->string('group_label')->nullable(); // contoh: "Identitas & Foto"

            // CSV ekstensi yang diizinkan, contoh "jpg,jpeg" (Passport/Pass Photo)
            // atau "pdf,jpg,jpeg" (kebanyakan dokumen lain).
            $table->string('allowed_extensions')->default('pdf,jpg,jpeg');

            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
