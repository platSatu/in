<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FIX (15 September 2026, permintaan user -- flow "pilih kampus -> degree ->
| jurusan" di halaman publik /universities): 1 baris university_profile_degrees
| (sebelumnya cuma degree+intake+duration) sekarang jadi 1 baris "Course"
| lengkap di bawah 1 Degree, dengan 5 kolom baru:
| - course_name         : nama mata pelajaran/jurusan baris ini (mis. "Teknik
|                          Informatika") -- BEDA dari UniversityProfile::field
|                          yang cuma 1 nilai untuk SATU Profile; sekarang tiap
|                          baris course di bawah Degree yang sama boleh beda
|                          jurusan (mis. Bachelor -> Teknik Informatika,
|                          Bachelor -> Bisnis Internasional).
| - starting_date        : tanggal mulai kelas.
| - application_deadline : batas akhir pendaftaran.
| - language             : bahasa pengantar KHUSUS course ini (sebelumnya cuma
|                          ada di level Profile/UniversityProfile::language
|                          yang berlaku utk semua baris di bawahnya -- kolom
|                          lama itu TIDAK dihapus, cuma tidak lagi jadi
|                          satu-satunya sumber, lihat UniversityProfileController).
| - tuition_fee          : nominal SPP course ini (angka tunggal, sengaja
|                          terpisah dari tabel university_profile_payments
|                          yang tetap dipakai untuk breakdown biaya lain --
|                          Registration Fee, Deposit China, dst -- di alur
|                          Apply Kampus).
|
| Kolom `degree` yang sudah ada TIDAK diubah tipenya (tetap string bebas di
| DB) -- pembatasan ke 4 pilihan (Diploma/Bachelor/Master/PhD) cukup di sisi
| form (select) + validasi controller, tidak perlu enum DB supaya data lama
| yang mungkin belum sesuai 4 pilihan itu tidak langsung invalid.
|
| Semua kolom baru nullable, mengikuti pola kolom lain di tabel ini (intake,
| duration) -- tidak semua baris course wajib lengkap semua field.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            if (!Schema::hasColumn('university_profile_degrees', 'course_name')) {
                $table->string('course_name')->nullable()->after('degree');
            }

            if (!Schema::hasColumn('university_profile_degrees', 'starting_date')) {
                $table->date('starting_date')->nullable()->after('duration');
            }

            if (!Schema::hasColumn('university_profile_degrees', 'application_deadline')) {
                $table->date('application_deadline')->nullable()->after('starting_date');
            }

            if (!Schema::hasColumn('university_profile_degrees', 'language')) {
                $table->string('language')->nullable()->after('application_deadline');
            }

            if (!Schema::hasColumn('university_profile_degrees', 'tuition_fee')) {
                $table->unsignedBigInteger('tuition_fee')->nullable()->after('language');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_profile_degrees', function (Blueprint $table) {
            foreach (['course_name', 'starting_date', 'application_deadline', 'language', 'tuition_fee'] as $column) {
                if (Schema::hasColumn('university_profile_degrees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
