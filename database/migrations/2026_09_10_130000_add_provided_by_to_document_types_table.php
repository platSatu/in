<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Fase 1 -- fitur "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).
|
| Kolom BARU 'provided_by' -- bedakan dokumen yang diupload SISWA (default,
| semua DocumentType yang sudah ada TIDAK berubah perilakunya sama sekali)
| dari dokumen yang diupload ADMIN untuk didownload siswa (Offer Letter,
| Passport -- lihat Fase 4). Dipakai nanti oleh halaman upload siswa untuk
| menyembunyikan kolom upload pada jenis dokumen yang 'provided_by' =
| 'admin' (section terpisah "Documents from Admin", view-only).
|
| Default 'student' supaya SEMUA baris document_types yang sudah ada
| (Passport, Transcript, dst) otomatis tidak berubah -- tidak perlu
| backfill data manual.
|
| Entry DocumentType untuk Offer Letter & Passport (admin) BELUM diseed di
| sini -- menyusul di Fase 4 bersamaan dengan perubahan tampilannya, supaya
| tidak ada jeda waktu di mana baris ini nongol di checklist upload siswa
| sebelum tampilannya siap menangani provided_by='admin' dengan benar.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('provided_by')->default('student')->after('template_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('provided_by');
        });
    }
};
