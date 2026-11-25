<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah Student dari menu Course > Student INA YULE (25 November 2026).
 * inayule_registered_at = kapan murid didaftarkan sebagai murid INA YULE oleh admin
 * (tombol Tambah Student atau Import Excel). Murid ini langsung tampil di Student INA YULE
 * walau belum membeli paket, dan tetap satu data yang sama di menu Students.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'inayule_registered_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->timestamp('inayule_registered_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'inayule_registered_at')) {
            Schema::table('students', fn (Blueprint $table) => $table->dropColumn('inayule_registered_at'));
        }
    }
};
