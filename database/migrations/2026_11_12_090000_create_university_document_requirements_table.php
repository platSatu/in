<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dokumen Persyaratan per kampus (12 November 2026, acuan: Google Sheet "Requirements University").
 * Satu baris = satu jenis dokumen yang diminta kampus itu, beserta wajib/opsional, catatan,
 * batasan jenjang & bahasa pengantar, dan contoh/template khusus kampus.
 * Kampus tanpa baris sama sekali = belum diatur, student tetap melihat semua dokumen seperti sebelumnya.
 *
 * Sekalian menambah jenis dokumen dari sheet yang belum ada (hanya kalau code-nya belum ada).
 */
return new class extends Migration
{
    private const TYPES = [
        ['code' => 'personal_statement', 'label' => 'Personal Statement', 'group_label' => 'Academic', 'allowed_extensions' => 'pdf', 'sort_order' => 75],
        ['code' => 'standardized_test', 'label' => 'Standardized Test Scores (SAT / IB / A-Level)', 'group_label' => 'Certificate', 'allowed_extensions' => 'pdf,jpg,jpeg', 'sort_order' => 135],
        ['code' => 'no_drug_statement', 'label' => 'No Drug Addiction Statement', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'sort_order' => 152],
        ['code' => 'mental_health_survey', 'label' => 'International Student Mental Health Survey', 'group_label' => 'Kesehatan & Legal', 'allowed_extensions' => 'pdf,jpg,jpeg', 'sort_order' => 154],
        ['code' => 'supervisor_acceptance_letter', 'label' => 'Acceptance Letter from Supervisor', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf,jpg,jpeg', 'sort_order' => 165],
        ['code' => 'cv', 'label' => 'CV', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'pdf', 'sort_order' => 175],
        ['code' => 'self_intro_video', 'label' => 'Self-Introduction Video', 'group_label' => 'Supporting Letter', 'allowed_extensions' => 'mp4,mov', 'sort_order' => 185],
    ];

    public function up(): void
    {
        // Sisa percobaan migrate pertama yang gagal (nama index unik terlalu panjang untuk MySQL): tabelnya
        // sempat terbuat tanpa index & migration-nya belum tercatat, jadi dibuat ulang dari awal di sini.
        Schema::dropIfExists('university_document_requirements');

        Schema::create('university_document_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignUuid('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->string('note', 500)->nullable();          // mis. "HSK 4, score 210+" / "at least RMB 30,000"
            $table->string('degree_scope', 20)->nullable();   // null = semua, bachelor, postgraduate
            $table->string('language_scope', 20)->nullable(); // null = semua, english, chinese
            $table->string('template_file_path')->nullable(); // contoh/template khusus kampus, path relatif ke public/
            $table->timestamps();

            // Nama index ditulis pendek: nama otomatisnya melebihi batas 64 karakter MySQL.
            $table->unique(['university_id', 'document_type_id'], 'udr_university_document_unique');
        });

        foreach (self::TYPES as $type) {
            if (DB::table('document_types')->where('code', $type['code'])->exists()) {
                continue;
            }

            DB::table('document_types')->insert($type + [
                'id' => (string) Str::uuid(),
                'provided_by' => 'student',
                'is_required' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('university_document_requirements');

        // Jenis baru hanya dihapus kalau belum ada file yang diupload.
        $ids = DB::table('document_types')->whereIn('code', array_column(self::TYPES, 'code'))->pluck('id');
        $used = DB::table('application_documents')->whereIn('document_type_id', $ids)->distinct()->pluck('document_type_id');
        DB::table('document_types')->whereIn('id', $ids->diff($used))->delete();
    }
};
