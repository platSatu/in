<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabel anak baru untuk rincian biaya (Payment) per University Profile,
| dipakai lewat fitur "add row" -- sama polanya dengan
| university_profile_degrees: pilih lokasi bayar (Indonesia / China), nama
| item biaya (mis. "Tuition Fee", "Dormitory Single Room"), dan jumlahnya.
| Mata uang sengaja tidak jadi kolom terpisah -- disimpulkan dari `location`
| (Indonesia = Rp, China = RMB/Yuan) di sisi tampilan, bukan disimpan di DB.
|
| Semua kolom selain id/university_profile_id nullable, karena rincian
| biaya tiap kampus beda-beda dan tidak semua profile butuh diisi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_profile_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('university_profile_id', 36)->index();
            $table->char('user_id', 36)->nullable()->index();

            $table->string('location')->nullable(); // 'indonesia' atau 'china'
            $table->string('name')->nullable(); // mis. "Registration Fee", "Tuition Fee"
            $table->unsignedBigInteger('amount')->nullable();

            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_profile_payments');
    }
};
