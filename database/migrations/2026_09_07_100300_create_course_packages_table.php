<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE PACKAGES ===
| Katalog produk siap jual: 1 baris = 1 SKU yang bisa langsung dipilih end
| user (kombinasi course_type + course_class + course_level + duration +
| price + credits). Dibuat SETELAH ketiga master (course_type/course_class/
| course_level) supaya foreign key-nya valid -- lihat migration
| 2026_09_07_100000/100100/100200.
|
| `restrictOnDelete()` dipakai (bukan cascade) di ketiga FK: sengaja supaya
| master data (Type/Class/Level) yang MASIH dipakai 1+ package tidak bisa
| dihapus begitu saja dari menunya masing-masing -- mencegah package yang
| sudah terlanjur dibeli student kehilangan referensinya secara diam-diam.
| Admin harus pindahkan/nonaktifkan package yang memakainya dulu baru bisa
| hapus master data tsb.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable()->index();

            $table->string('name');

            $table->foreignUuid('course_type_id')
                ->constrained('course_type')
                ->restrictOnDelete();

            $table->foreignUuid('course_class_id')
                ->constrained('course_class')
                ->restrictOnDelete();

            $table->foreignUuid('course_level_id')
                ->constrained('course_level')
                ->restrictOnDelete();

            // Durasi dipisah nilai + satuan (bukan 1 string bebas "3 Bulan")
            // supaya tetap bisa diolah/di-sort dan tervalidasi rapi, tapi
            // satuannya string (bukan enum) supaya gampang ditambah opsi baru
            // (mis. "sesi"/"minggu") tanpa migration lagi.
            $table->unsignedInteger('duration_value');
            $table->string('duration_unit', 20)->default('month');

            // decimal TANPA ->unsigned(): MySQL 8 sudah deprecate UNSIGNED
            // pada kolom decimal/float -- validasi "tidak boleh negatif"
            // cukup di layer aplikasi (lihat CoursePackageController).
            $table->decimal('price', 12, 2)->default(0);

            // Jumlah kredit yang didapat sekali beli package ini -- decimal
            // karena dari mockup nilainya bisa pecahan (mis. 1.5).
            $table->decimal('credits', 8, 2)->default(0);

            $table->text('description')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_packages');
    }
};
