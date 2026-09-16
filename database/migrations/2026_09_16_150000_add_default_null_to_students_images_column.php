<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| FIX (permintaan user, 16 September 2026): kolom `images` di tabel
| `students` ternyata NOT NULL tanpa default value di database -- tapi
| TIDAK ADA migration manapun di repo ini yang membuat kolom itu (tabel
| `students` sendiri tidak punya migration create_students_table di sini
| sama sekali, kemungkinan besar dibuat manual lewat SQL/phpMyAdmin dulu,
| bukan lewat migration Laravel). Ini bikin INSERT gagal
| ("SQLSTATE[HY000]: 1364 Field 'images' doesn't have a default value")
| setiap kali bikin Student baru TANPA upload foto, baik dari halaman
| admin "+ Add Student" (StudentController::store()) MAUPUN dari alur
| publik Form Wizard (StudentIdentityResolver::findOrCreate()) -- kedua
| tempat itu SENGAJA tidak isi 'images' kalau tidak ada file yang
| diupload (rule validasinya 'nullable'), yang normalnya valid untuk
| kolom nullable, tapi kolom di DB-nya ternyata TIDAK nullable.
|
| Migration ini baca dulu tipe kolom `images` yang SEBENARNYA ada di DB
| (lewat SHOW COLUMNS, bukan asumsi VARCHAR(255)) supaya ALTER TABLE-nya
| tidak mengubah tipe/panjang kolom yang sudah ada -- cuma bikin nullable
| dengan default NULL. Aman dijalankan berkali-kali (idempotent, dicek
| dulu apakah sudah nullable).
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'images')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `students` WHERE Field = 'images'");

        if (! $column || strtoupper($column->Null) === 'YES') {
            // Sudah nullable (atau kolomnya tidak ketemu) -- tidak perlu diubah.
            return;
        }

        DB::statement("ALTER TABLE `students` MODIFY `images` {$column->Type} NULL DEFAULT NULL");
    }

    public function down(): void
    {
        // SENGAJA tidak di-revert ke NOT NULL -- balik ke situasi macet
        // seperti sebelum fix ini tidak ada gunanya dan berisiko bikin
        // error yang sama muncul lagi kalau migration ini di-rollback
        // tanpa sengaja.
    }
};
