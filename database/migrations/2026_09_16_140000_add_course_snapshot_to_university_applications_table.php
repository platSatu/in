<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FIX (permintaan user, 16 September 2026): waktu dicek ulang, ternyata
| kolom Jurusan (Course, university_profile_degrees.course_name) yang
| dipilih siswa di form Apply -- fitur "select jurusan otomatis
| menampilkan Intake & Duration" -- TIDAK PERNAH disimpan ke
| university_applications. Cuma degree/intake/duration/language yang
| di-snapshot (lihat migration create_university_applications_table),
| dan itu SEMUANYA bisa sama persis untuk beberapa Course berbeda dalam 1
| Program (contoh: "Aeronautical Engineering" dan "Artificial
| Intelligence" bisa sama-sama Bachelor/September/4 Years) -- jadi tanpa
| kolom ini, admin TIDAK BISA tahu jurusan mana sebenarnya yang dipilih
| siswa begitu ada lebih dari 1 Course dengan Degree/Intake/Duration yang
| sama, walaupun siswanya sendiri sudah pilih jurusan yang benar di form.
|
| 'degree_intake_id': referensi ke baris Course (university_profile_degrees)
| yang dipilih -- dibiarkan TANPA foreign key constraint (konsisten dengan
| kolom snapshot lain di tabel ini), karena baris Course itu bisa saja
| diedit/dihapus admin di kemudian hari dan aplikasi yang sudah submit
| tidak boleh ikut berubah/rusak datanya.
| 'course_name': snapshot NAMA jurusannya sendiri saat submit, supaya
| tetap kebaca WALAUPUN baris Course aslinya sudah dihapus/diubah admin.
|
| Kolom nullable, cuma menambah -- tidak mengubah/menghapus apapun yang
| sudah ada. Aplikasi LAMA yang sudah submit sebelum fix ini otomatis
| NULL di kedua kolom baru ini (tidak bisa direkonstruksi lagi jurusan
| persisnya, tapi tidak menghalangi apapun yang sudah jalan).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('university_applications', 'degree_intake_id')) {
                $table->char('degree_intake_id', 36)->nullable()->after('university_profile_id');
            }

            if (! Schema::hasColumn('university_applications', 'course_name')) {
                $table->string('course_name')->nullable()->after('degree');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            if (Schema::hasColumn('university_applications', 'degree_intake_id')) {
                $table->dropColumn('degree_intake_id');
            }

            if (Schema::hasColumn('university_applications', 'course_name')) {
                $table->dropColumn('course_name');
            }
        });
    }
};
