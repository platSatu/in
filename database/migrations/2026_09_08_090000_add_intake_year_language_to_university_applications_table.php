<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tambahan kecil untuk tabel university_applications (fase 4, fitur form
| Apply): 'intake_year' (tahun angkatan, mis. 2026 -- dipilih/diisi sendiri
| oleh calon siswa saat submit, karena pilihan Intake dari kampus cuma
| "March"/"September" tanpa tahun) dan 'language' (snapshot bahasa
| pengantar dari UniversityProfile->language saat submit, konsisten dengan
| degree/intake/duration yang juga di-snapshot -- lihat catatan di migration
| create_university_applications_table).
|
| Dibutuhkan untuk laporan "angkatan tahun 2026" per kampus (permintaan
| user). Kolom nullable, cuma menambah -- tidak mengubah/menghapus apapun
| yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('university_applications', 'intake_year')) {
                $table->unsignedSmallInteger('intake_year')->nullable()->after('intake');
            }

            if (! Schema::hasColumn('university_applications', 'language')) {
                $table->string('language')->nullable()->after('degree');
            }
        });

        Schema::table('university_applications', function (Blueprint $table) {
            $table->index('intake_year');
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->dropIndex(['intake_year']);

            if (Schema::hasColumn('university_applications', 'intake_year')) {
                $table->dropColumn('intake_year');
            }

            if (Schema::hasColumn('university_applications', 'language')) {
                $table->dropColumn('language');
            }
        });
    }
};
