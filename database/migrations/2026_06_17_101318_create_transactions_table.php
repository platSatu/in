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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->enum('type', ['debit', 'credit']);

            $table->decimal('amount', 15, 2);

            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);

            $table->text('description')->nullable();

            // supaya tahu transaksi ini dari mana
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();

            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])
                  ->default('pending');

            $table->timestamp('transaction_date')->nullable();

            $table->timestamps();

            // optional FK
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
