<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BUGFIX PRODUKSI (live, kejadian saat publish quiz HSK): tabel
     * `students` ternyata punya UNIQUE constraint di kolom `email` (nama
     * index bawaan Laravel: students_email_unique) yang TIDAK PERNAH
     * tercatat lewat migration manapun di repo ini sebelumnya (kemungkinan
     * dibuat langsung di database dulu, di luar migration).
     *
     * Constraint ini BERTABRAKAN LANGSUNG dengan cara sistem mengenali
     * identitas Student sekarang: FrontendController::findOrCreateStudent()
     * sengaja pakai NOMOR HP (bukan email) sebagai kunci identitas --
     * dikomentari eksplisit di situ "Nomor HP dipakai sebagai kunci
     * identitas Student (biar tidak dobel baris per orang)".
     *
     * Akibatnya: begitu ada 2 pengisian placement test dengan HP BERBEDA
     * tapi email yang SAMA (mis. peserta pakai email kantor/keluarga yang
     * dipakai bersama, atau isi ulang pakai nomor lain), baris kedua GAGAL
     * TOTAL dengan error mentah "SQLSTATE[23000]... Duplicate entry ...
     * for key 'students_email_unique'" -- submission peserta itu HILANG
     * sama sekali (crash terjadi di findOrCreateStudent(), SEBELUM
     * FormSubmission/FormAnswer sempat dibuat sama sekali).
     *
     * Kejadian nyata yang memicu fix ini: email hans.inatrip@gmail.com,
     * saat form sedang di-publish/dipromosikan ke banyak peserta.
     *
     * Sudah dicek: tidak ada bagian lain di codebase yang mengandalkan
     * email di tabel `students` harus unik (tidak ada Student::where(
     * 'email', ...) untuk keperluan pencarian/otentikasi di mana pun) --
     * jadi aman dilepas, dan justru menyamakan constraint database dengan
     * desain yang sudah berjalan di kode (HP sebagai kunci, email boleh
     * sama di beberapa baris Student yang berbeda).
     */
    public function up(): void
    {
        $indexExists = collect(
            DB::select("SHOW INDEX FROM students WHERE Key_name = 'students_email_unique'")
        )->isNotEmpty();

        if ($indexExists) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_email_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SENGAJA TIDAK di-restore otomatis: setelah constraint ini
        // dilepas, sangat mungkin sudah ada baris `students` dengan email
        // yang sama persis di production (itu justru tujuan fix ini) --
        // re-add UNIQUE constraint di sini akan GAGAL kalau itu terjadi.
        // Kalau benar-benar perlu rollback, bersihkan dulu duplikat email
        // yang muncul setelah fix ini secara manual, baru re-add manual:
        // Schema::table('students', fn (Blueprint $t) => $t->unique('email'));
    }
};
