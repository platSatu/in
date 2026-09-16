<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabel anak baru untuk rincian Scholarship (Name/Price/Currency) per
| University Profile, dipakai lewat fitur "add row" -- sama persis
| polanya dengan university_profile_payments (lihat migration
| create_university_profile_payments_table). Section "add row" ini di
| form admin (create/edit) cuma ditampilkan kalau "Scholarship Available"
| dipilih "Yes", tapi baris-baris di sini TIDAK divalidasi terikat ke
| nilai scholarship_available -- kalau nanti diubah balik ke "No",
| baris lama dibiarkan saja (tidak otomatis dihapus), sama seperti
| Degree/Payment yang juga tidak bergantung ke kolom lain.
|
| Semua kolom selain id/university_profile_id nullable, karena tidak
| semua profile perlu diisi datanya.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_profile_scholarships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('university_profile_id', 36)->index();
            $table->char('user_id', 36)->nullable()->index();

            $table->string('name')->nullable(); // mis. "Full Scholarship", "Partial Scholarship"
            $table->unsignedBigInteger('price')->nullable();
            $table->string('currency')->nullable(); // 'rupiah' atau 'yuan'

            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_profile_scholarships');
    }
};
