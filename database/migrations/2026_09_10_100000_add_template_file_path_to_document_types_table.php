<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Fitur "Download Template" (10 September 2026) -- beberapa jenis dokumen
| (contoh pertama: Medical Certificate) butuh siswa DOWNLOAD dulu form
| kosongnya, isi/tanda tangan, baru diupload lagi lewat kolom upload yang
| sudah ada (ApplicationDocumentController::update() -- tidak berubah sama
| sekali, cukup pakai input file yang sama).
|
| Kolom ini nullable & opsional per DocumentType -- kalau kosong, jenis
| dokumen itu tampil seperti biasa tanpa tombol download apa pun.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('template_file_path')->nullable()->after('allowed_extensions');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('template_file_path');
        });
    }
};
