<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah 2 kolom opsional ke `forms`: background_image & logo (path relatif
     * ke public/, sama polanya dengan logo/banner di tabel `universities`).
     *
     * Bedanya dengan logo/banner di `universities`: kolom di sini NULLABLE
     * DENGAN default NULL (bukan NOT NULL tanpa default), jadi kalau admin
     * tidak upload apa-apa, kolomnya tetap NULL dan halaman quiz publik
     * (frontend/form-wizard.blade.php) otomatis pakai background/logo default
     * yang sudah ada sekarang (public/image/bg_quiz.jpeg & frontend/img/Logo.png)
     * — sesuai permintaan: boleh kosong, default seperti sekarang. Favicon
     * TIDAK ikut dikustomisasi lewat kolom ini (favicon tetap Logo.png terus).
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('background_image')->nullable()->after('description');
            $table->string('logo')->nullable()->after('background_image');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['background_image', 'logo']);
        });
    }
};
