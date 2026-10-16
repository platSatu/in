<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 16 Oktober 2026: file contoh di halaman upload dokumen siswa.
 * - template_file_path = form kosong yang diisi lalu diupload ulang ("Download Template").
 * - guide_file_path    = panduan/prosedur cara mengurus dokumen ("Download Guide").
 * File ada di public/document-templates/. Lihat juga DocumentTypeSeeder.
 */
return new class extends Migration
{
    private const FILES = [
        'graduation_letter_expected' => ['template_file_path' => 'document-templates/Expected-Graduate-Letter.pdf'],
        'transcript' => ['template_file_path' => 'document-templates/Transkrip-1-5.pdf'],
        'medical_checkup' => [
            'template_file_path' => 'document-templates/MEDICAL-CHECK-UP-FORM.pdf',
            'guide_file_path' => 'document-templates/Prosedur-Medical-Check-Up-INA-STUDY.pdf',
        ],
        'skck' => ['guide_file_path' => 'document-templates/Prosedur-Pembuatan-SKCK-INA-STUDY.pdf'],
        'recommendation_letter' => ['template_file_path' => 'document-templates/Recommendation-Letter.pdf'],
    ];

    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('guide_file_path')->nullable()->after('template_file_path');
        });

        foreach (self::FILES as $code => $paths) {
            DB::table('document_types')->where('code', $code)->update($paths);
        }
    }

    public function down(): void
    {
        DB::table('document_types')->where('code', 'medical_checkup')->update(['template_file_path' => 'document-templates/medical_certificate.pdf']);
        DB::table('document_types')->whereIn('code', ['graduation_letter_expected', 'transcript', 'recommendation_letter'])->update(['template_file_path' => null]);

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('guide_file_path');
        });
    }
};
