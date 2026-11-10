<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoice Ina Visa (10 November 2026): link invoice publik untuk pembayaran form berjenis Ina Visa,
     * dikirim via WhatsApp & email setelah lunas. Sama dengan application_payments.invoice_token
     * (lihat InvoiceController & FormPaymentController::sendInvoiceNotification()).
     */
    public function up(): void
    {
        Schema::table('form_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('form_payments', 'invoice_token')) {
                $table->string('invoice_token')->unique()->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_payments', function (Blueprint $table) {
            if (Schema::hasColumn('form_payments', 'invoice_token')) {
                $table->dropUnique(['invoice_token']);
                $table->dropColumn('invoice_token');
            }
        });
    }
};
