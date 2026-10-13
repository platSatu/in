<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen yang boleh lebih dari 1 file per aplikasi (13 Oktober 2026),
 * mulai dari Any Certificate:
 *  - document_types.allows_multiple: tanda jenis yang boleh banyak file.
 *  - application_documents.title: nama sertifikat (mis. "Juara 1 Lomba").
 *  - unique (application_id, document_type_id) diganti index biasa; untuk
 *    jenis yang TIDAK boleh banyak, tetap 1 file per aplikasi dijaga di
 *    kode (updateOrCreate di dalam lock aplikasi). Dokumen lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->boolean('allows_multiple')->default(false)->after('provided_by');
        });

        DB::table('document_types')->where('code', 'any_certificate')->update(['allows_multiple' => true]);

        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('document_type_id');
            $table->index(['application_id', 'document_type_id'], 'application_documents_app_type_index');
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        // Gagal kalau sudah ada lebih dari 1 file untuk jenis yang sama --
        // sengaja, supaya data sertifikat tidak terhapus diam-diam.
        Schema::table('application_documents', function (Blueprint $table) {
            $table->unique(['application_id', 'document_type_id']);
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex('application_documents_app_type_index');
            $table->dropColumn('title');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('allows_multiple');
        });
    }
};
