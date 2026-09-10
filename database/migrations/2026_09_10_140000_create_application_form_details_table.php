<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FASE 3 -- fitur "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).
|
| Step 1 SETELAH Registration Fee lunas: siswa isi "Formulir" (form biodata
| lengkap, contoh aslinya form Word per-kampus seperti "Nanjing Tech Form"
| yang dikirim user) LANGSUNG di web -- BUKAN lagi upload file Word/PDF
| manual. Satu baris di sini = 1 UniversityApplication (relasi 1-ke-1, lihat
| UniversityApplication::formDetail()).
|
| Kolom-kolom di bawah PERSIS mengikuti daftar field yang diminta user dari
| contoh form aslinya (Name on Passport/Surname/Given Name, Chinese Name,
| Gender, dst) -- SEMUA nullable (kecuali terms_accepted_at yang WAJIB diisi
| sebelum lanjut ke Step 2 Upload Documents, lihat
| ApplicationFormController::update() & guard baru di
| ApplicationDocumentController).
|
| DocumentType 'formulir' (upload file manual) di-nonaktifkan (status jadi
| 'inactive', BUKAN dihapus -- lihat DocumentTypeSeeder) begitu fitur ini
| aktif, supaya tidak dobel dengan form web ini.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // char(36) TANPA foreign key constraint -- mengikuti konvensi
            // tabel Fase 5 lain (application_documents, application_payments,
            // dst), bukan uuid()->foreign()->cascadeOnDelete().
            $table->char('application_id', 36);

            $table->string('surname')->nullable();
            $table->string('given_name')->nullable();
            $table->string('chinese_name')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('passport_no')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('telephone_no')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('hobby')->nullable();
            $table->string('parents_name')->nullable();
            $table->string('parents_phone')->nullable();
            $table->string('parents_occupation')->nullable();
            $table->text('home_address')->nullable();
            $table->string('email')->nullable();
            $table->string('religion')->nullable();
            $table->string('highest_degree_obtained')->nullable();
            $table->string('field_of_study_in_china')->nullable();
            $table->string('financial_support_by')->nullable();
            // 'scholarship' | 'self_sponsored' -- lihat
            // ApplicationFormDetail::SPONSORSHIP_*.
            $table->string('sponsorship_type')->nullable();

            // Diisi timestamp begitu siswa centang & submit Terms & Condition
            // di akhir form -- NULL berarti Step 1 belum benar-benar selesai
            // (dipakai ApplicationDocumentController buat gerbang ke Step 2).
            $table->timestamp('terms_accepted_at')->nullable();

            $table->timestamps();

            $table->unique('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_form_details');
    }
};
