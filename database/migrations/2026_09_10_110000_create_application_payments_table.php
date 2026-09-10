<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Fase 1 -- fitur "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).
|
| Tabel pembayaran KHUSUS Aplikasi Kuliah (terpisah dari form_payments yang
| dipakai Quiz Form) -- satu baris = satu transaksi gateway untuk satu
| "purpose" (registration_fee ATAU departure_fee) milik satu
| university_applications. Satu aplikasi bisa punya BEBERAPA baris di sini
| (percobaan bayar ulang kalau expired/failed, dan 2 purpose berbeda),
| makanya BUKAN kolom langsung di university_applications.
|
| Pola & nama kolom SENGAJA dibuat mirip form_payments (reuse
| PaymentGatewayFactory & webhook handler yang sudah ada) supaya konsisten
| dengan fitur pembayaran yang sudah jalan -- bedanya cuma ditambah
| 'purpose' dan 'invoice_token' (link invoice yang dikirim via WhatsApp
| setelah pembayaran sukses, lihat Fase 6).
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('application_id', 36);

            // registration_fee | departure_fee -- lihat konstanta di model
            // ApplicationPayment. Nominal KEDUANYA diisi manual oleh admin
            // per aplikasi (BUKAN otomatis dari data kampus/major yang
            // sifatnya cuma estimasi), lihat kolom registration_fee_amount &
            // deposit_fee_china_amount di university_applications (dipakai
            // ulang sebagai nominal Departure Fee, cuma beda label tampilan).
            $table->string('purpose');

            $table->char('payment_gateway_id', 36)->nullable();
            $table->string('order_id')->unique();
            $table->string('gateway')->nullable(); // duitku | midtrans | ipaymu

            // Nominal integer Rupiah, konsisten dengan pola
            // university_profile_payments.amount &
            // university_applications.registration_fee_amount (bukan
            // decimal seperti form_payments.amount).
            $table->unsignedBigInteger('amount')->default(0);

            // pending, paid, failed, expired -- persis pola form_payments.
            $table->string('status')->default('pending');

            $table->string('payment_method')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_callback')->nullable();

            // Token unik untuk link invoice publik (/invoice/{token}) yang
            // dikirim ke siswa via WhatsApp begitu status jadi "paid" --
            // lihat Fase 6. Nullable karena baru di-generate SETELAH paid,
            // bukan saat transaksi dibuat.
            $table->string('invoice_token')->unique()->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('application_id');
            $table->index(['application_id', 'purpose']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_payments');
    }
};
