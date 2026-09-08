<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Riwayat versi file dokumen yang sudah DIGANTI (upload ulang). Setiap kali
| application_documents ditimpa file baru, isi lama baris itu disalin ke
| sini dulu -- sesuai permintaan user supaya versi lama tetap bisa dilihat
| admin, bukan langsung hilang.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_document_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('application_document_id', 36);

            $table->string('file_path');
            $table->string('original_filename')->nullable();

            $table->char('uploaded_by_user_id', 36)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('replaced_at')->nullable();

            $table->timestamps();

            $table->index('application_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_document_histories');
    }
};
