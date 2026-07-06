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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->char('application_category_id', 36)->index();

            $table->string('code_vouchers')->unique();

            $table->enum('status', ['active', 'inactive', 'expired', 'used'])
                  ->default('active');

            $table->date('valid_from');

            $table->date('valid_until');

            $table->timestamps();

            // optional foreign keys (kalau sudah ada relasi)
            // $table->foreign('application_category_id')->references('id')->on('application_categories')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
