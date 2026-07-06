<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran_form_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);
            $table->char('parent_id', 36)->nullable();
            $table->char('pembayaran_form_id', 36);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->enum('payment_status', [
                'paid',
                'pending',
                'expired',
                'failed',
            ])->default('pending');

            $table->string('payment_method')->nullable();

            $table->timestamp('payment_date')->nullable();

            $table->string('order_id')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('parent_id');
            $table->index('pembayaran_form_id');
            $table->index('payment_status');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_form_links');
    }
};
