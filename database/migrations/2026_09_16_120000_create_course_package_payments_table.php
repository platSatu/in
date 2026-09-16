<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak 1 percobaan CHECKOUT pembelian package berbayar (deposit / gateway /
 * campuran keduanya) -- pola & alasan SAMA PERSIS dengan deposit_payments
 * (lihat docblock App\Models\DepositPayment): tabel INI cuma catatan sesi
 * checkout, BUKAN sumber kebenaran saldo/credit. Saldo Deposit & CourseCredit
 * yang SEBENARNYA hanya berubah lewat
 * App\Http\Controllers\StudentPortal\InaYulePackageWebhookController (untuk
 * porsi gateway) atau langsung di dalam 1 DB transaction di
 * InaYulePackageCheckoutController::store() (untuk pembelian yang 100%
 * tertutup saldo Deposit, karena tidak ada apa pun yang perlu dikonfirmasi
 * pihak ketiga).
 *
 * `deposit_portion` & `gateway_portion` adalah split "otomatis" (pakai saldo
 * Deposit dulu sebisa mungkin, sisanya baru ditagih ke payment gateway) yang
 * DIHITUNG ULANG di server saat checkout -- lihat docblock
 * InaYulePackageCheckoutController. `price_total` & `credits_granted` adalah
 * SNAPSHOT harga/credits package saat checkout dibuat (sama alasannya dengan
 * course_package_purchases.price_paid/credits_granted) -- dipakai lagi oleh
 * webhook supaya tidak perlu percaya harga/credits package "sekarang" yang
 * mungkin sudah diubah admin di antara checkout dibuat & gateway konfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_package_payments')) {
            return;
        }

        Schema::create('course_package_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Sengaja simpan DUA identitas: student_id (buat CourseCredit
            // nanti) dan user_id (buat Deposit nanti, ledger Deposit
            // di-key oleh user_id, bukan student_id) -- lihat docblock
            // InaYulePackageCheckoutController soal kenapa keduanya perlu.
            $table->char('student_id', 36)->index();
            $table->char('user_id', 36)->index();

            $table->foreignUuid('course_package_id')->constrained('course_packages')->restrictOnDelete();
            $table->foreignUuid('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->foreignUuid('course_package_purchase_id')->nullable()->constrained('course_package_purchases')->nullOnDelete();

            $table->string('order_id')->unique();
            $table->string('gateway')->nullable();

            // Snapshot kontak student/user saat checkout dibuat -- dipakai
            // gateway driver (customer_details Midtrans, buyerName/Email/Phone
            // iPaymu, customerVaName Duitku) supaya TIDAK perlu join ulang ke
            // Student/User saat webhook diproses belakangan (pola SAMA PERSIS
            // dengan DepositPayment.name/email/handphone).
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('handphone')->nullable();

            $table->decimal('price_total', 12, 2)->default(0);
            $table->decimal('deposit_portion', 12, 2)->default(0);
            $table->decimal('gateway_portion', 12, 2)->default(0);
            $table->decimal('credits_granted', 8, 2)->default(0);

            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_callback')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Nama index eksplisit -- default auto-generated Laravel bisa
            // lewat batas 64 karakter MySQL (lihat insiden migration
            // course_package_purchases sebelumnya).
            $table->index(['student_id', 'status'], 'cpp_payments_student_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_package_payments');
    }
};
