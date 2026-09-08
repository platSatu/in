<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tambah kolom nullable 'fee_type' ke tabel biaya kampus yang SUDAH ADA
| (university_profile_payments), supaya fitur Apply bisa otomatis tahu
| baris mana yang "Registration Fee" tanpa menebak dari teks nama bebas.
| Baris lama otomatis null (aman, tidak wajib diisi ulang kecuali kampus
| itu mau dipakai fitur Apply).
|
| SATU-SATUNYA migration di fase ini yang menyentuh tabel LAMA -- cuma
| nambah 1 kolom nullable, tidak mengubah/menghapus kolom apapun yang ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_profile_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('university_profile_payments', 'fee_type')) {
                // registration_fee, tuition_fee, dormitory_fee, deposit_china, other
                $table->string('fee_type')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_profile_payments', function (Blueprint $table) {
            if (Schema::hasColumn('university_profile_payments', 'fee_type')) {
                $table->dropColumn('fee_type');
            }
        });
    }
};
