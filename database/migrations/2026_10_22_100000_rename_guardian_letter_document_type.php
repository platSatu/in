<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 22 Oktober 2026: perjelas Guardian Letter hanya untuk siswa di bawah 17 tahun.
 * Hanya label, 'code' tidak disentuh supaya file yang sudah diupload tetap terhubung.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_types')->where('code', 'guardian_letter')
            ->update(['label' => 'Guardian Letter (if student under 17 years old)', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('document_types')->where('code', 'guardian_letter')
            ->update(['label' => 'Guardian Letter', 'updated_at' => now()]);
    }
};
