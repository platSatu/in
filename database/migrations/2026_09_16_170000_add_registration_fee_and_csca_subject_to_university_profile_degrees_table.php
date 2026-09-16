<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FIX (permintaan user, 16 September 2026): sebelum ini, nominal
| "Registration Fee" untuk 1 aplikasi (university_applications.
| registration_fee_amount) HARUS diisi manual oleh admin SETELAH siswa
| submit form Apply (lewat Quiz\UniversityApplicationController::
| updateFees(), lihat migration add_fee_type_to_university_profile_
| payments_table & catatan FASE 2 di ApplyController::store()) -- user
| minta ini diubah supaya nominalnya sudah ditentukan DI DEPAN (saat admin
| bikin/edit Degree & Course), bukan belakangan, supaya begitu siswa Apply
| & submit, otomatis LANGSUNG bisa lanjut bayar tanpa nunggu admin isi
| dulu.
|
| 2 kolom baru ditambahkan ke university_profile_degrees (1 baris di sini
| = 1 Course, lihat migration add_course_fields_to_university_profile_
| degrees_table):
| - registration_fee_amount : nominal Registration Fee (Rupiah) khusus
|                              Course ini -- di-snapshot ke
|                              university_applications.registration_fee_amount
|                              saat siswa submit Apply (lihat
|                              ApplyController::store()), MENGGANTIKAN alur
|                              isi manual admin per-aplikasi yang lama.
| - csca_subject             : kategori/mata ujian CSCA untuk Course ini
|                              (CSCA Math / CSCA Physics / CSCA Humanities /
|                              CSCA Chemistry) -- pilihan tetap, lihat
|                              UniversityProfileDegree::CSCA_SUBJECTS.
|
| Keduanya nullable (boleh dikosongkan), mengikuti pola kolom lain di tabel
| ini (intake, duration, tuition_fee, dst).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            if (!Schema::hasColumn('university_profile_degrees', 'registration_fee_amount')) {
                $table->unsignedBigInteger('registration_fee_amount')->nullable()->after('tuition_fee');
            }

            if (!Schema::hasColumn('university_profile_degrees', 'csca_subject')) {
                $table->string('csca_subject')->nullable()->after('registration_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            foreach (['registration_fee_amount', 'csca_subject'] as $column) {
                if (Schema::hasColumn('university_profile_degrees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
