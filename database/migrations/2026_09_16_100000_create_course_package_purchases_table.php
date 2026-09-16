<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE PACKAGE PURCHASES ===
| Histori "apa yang sudah didapat/dibeli" oleh 1 student dari 1 CoursePackage
| -- lihat diskusi konsep InaYule (credit ledger) sebelum tabel ini dibangun.
|
| Dibangun MINIMAL dulu untuk kebutuhan sekarang (16 September 2026,
| permintaan user -- "knp angka nol disable juga ya button nya kan tidak
| ada pembayaran"): klaim package TRIAL (harga efektif Rp 0, lihat
| App\Models\CoursePackage::effectivePrice()) lewat
| InaYulePackageController::claimTrial() -- source='trial_claim'.
|
| Alur "beli pakai saldo Deposit" (source='deposit_purchase', BELUM
| dibangun -- masih tahap diskusi) akan pakai tabel yang SAMA ini nanti,
| makanya kolom `source` & `status` sudah disiapkan dari sekarang supaya
| tidak perlu migration tambahan lagi waktu itu dibangun.
|
| `price_paid` & `credits_granted` SENGAJA di-snapshot di sini (bukan selalu
| ambil live dari course_packages.price/credits) -- supaya riwayat yang
| sudah terjadi tidak ikut berubah kalau admin edit harga/credits package
| itu belakangan.
|
| `course_package_id` pakai restrictOnDelete() (konsisten dengan FK lain di
| course_packages) -- package yang sudah pernah diklaim/dibeli tidak boleh
| dihapus begitu saja, mencegah histori kehilangan referensinya.
|
| `student_id` SENGAJA tanpa foreign key constraint (char(36) + index biasa)
| -- mengikuti pola yang sama sudah dipakai class_enrollments.student_id di
| codebase ini (lihat migration create_class_enrollments_table).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_package_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('student_id', 36)->index();

            $table->foreignUuid('course_package_id')
                ->constrained('course_packages')
                ->restrictOnDelete();

            $table->decimal('price_paid', 12, 2)->default(0);
            $table->decimal('credits_granted', 8, 2)->default(0);

            // 'trial_claim' = klaim gratis package harga efektif Rp 0
            // (dibangun sekarang). 'deposit_purchase' = beli pakai saldo
            // Deposit (BELUM dibangun, disiapkan kolomnya saja).
            $table->enum('source', ['trial_claim', 'deposit_purchase'])->default('trial_claim');

            // 'completed' = langsung selesai (trial claim selalu instan,
            // tidak ada proses menunggu apapun). 'cancelled' disiapkan
            // untuk kebutuhan admin membatalkan/refund manual nanti.
            $table->enum('status', ['completed', 'cancelled'])->default('completed');

            $table->timestamps();

            $table->index(['student_id', 'course_package_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_package_purchases');
    }
};
