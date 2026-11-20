<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kwitansi pembayaran (jpg/png/pdf, opsional) yang diupload admin di card
 * Payment halaman detail Aplikasi Kuliah -- 20 November 2026.
 * File disimpan privat di storage/app/private/applications/payment-receipts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->string('payment_receipt_path')->nullable()->after('deposit_fee_china_amount');
            $table->string('payment_receipt_name')->nullable()->after('payment_receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('university_applications', function (Blueprint $table) {
            $table->dropColumn(['payment_receipt_path', 'payment_receipt_name']);
        });
    }
};
