<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu percobaan transaksi pembayaran dari wizard publik
     * (name/email/handphone diisi dulu di step 1, baru transaksi dibuat).
     * status HANYA boleh berubah jadi "paid" lewat webhook resmi gateway
     * (lihat FormPaymentController::handleWebhook), tidak pernah dari
     * request browser/redirect biasa — supaya tidak bisa dipalsukan.
     */
    public function up(): void
    {
        Schema::create('form_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('form_id', 36);
            $table->char('payment_gateway_id', 36)->nullable();
            $table->char('form_submission_id', 36)->nullable();

            $table->string('order_id', 50)->unique();
            $table->enum('gateway', ['duitku', 'midtrans', 'ipaymu']);

            $table->string('name');
            $table->string('email');
            $table->string('handphone', 20);
            $table->decimal('amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');

            $table->string('payment_method', 20)->nullable();
            $table->text('payment_url')->nullable();
            $table->string('gateway_reference')->nullable();

            $table->json('raw_response')->nullable();
            $table->json('raw_callback')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('form_id');
            $table->index('payment_gateway_id');
            $table->index(['form_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_payments');
    }
};
