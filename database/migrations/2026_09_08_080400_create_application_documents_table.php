<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| File dokumen TERKINI per (aplikasi, jenis dokumen) -- satu baris per
| kombinasi, unique constraint di bawah. Kalau siswa upload ulang, versi
| lama dipindah dulu ke application_document_histories (lihat migration
| berikutnya) sebelum baris ini ditimpa dengan file baru.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('application_id', 36);
            $table->char('document_type_id', 36);

            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();

            $table->char('uploaded_by_user_id', 36)->nullable();
            $table->timestamp('uploaded_at')->nullable();

            // pending, approved, rejected
            $table->string('review_status')->default('pending');
            $table->text('review_note')->nullable();
            $table->char('reviewed_by_user_id', 36)->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->unique(['application_id', 'document_type_id']);
            $table->index('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
