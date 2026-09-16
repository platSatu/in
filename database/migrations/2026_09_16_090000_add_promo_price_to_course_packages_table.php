<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| FIX (16 September 2026, permintaan user): tambah kolom `promo_price` ke
| course_packages -- nullable, kalau diisi berarti package itu lagi promo:
| harga asli (`price`) ditampilkan DICORET, `promo_price` yang jadi harga
| jual sebenarnya (lihat App\Models\CoursePackage::hasActivePromo() dan
| tampilannya di resources/views/student-portal/inayule/index.blade.php,
| tab "Buy Packages").
|
| Validasi "promo_price harus lebih kecil dari price" SENGAJA ditaruh di
| App\Http\Controllers\Course\CoursePackageController (rule 'lt:price'),
| bukan constraint di level DB, supaya pesan error-nya bisa ramah
| ditampilkan balik ke form admin.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('course_packages', 'promo_price')) {
                $table->decimal('promo_price', 12, 2)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_packages', function (Blueprint $table) {
            if (Schema::hasColumn('course_packages', 'promo_price')) {
                $table->dropColumn('promo_price');
            }
        });
    }
};
