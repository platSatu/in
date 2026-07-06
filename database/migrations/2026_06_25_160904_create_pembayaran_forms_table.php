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
        Schema::create('pembayaran_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);
            $table->char('pembayaran_category_id', 36);

            $table->string('name');

            $table->unsignedBigInteger('amount');

            $table->date('due_date')->nullable();

            $table->text('description')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index('pembayaran_category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_forms');
    }
};
