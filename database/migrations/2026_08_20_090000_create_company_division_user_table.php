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
        Schema::create('company_division_user', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('company_division_id', 36);
            $table->char('user_id', 36);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            // Optional indexes
            $table->index('company_division_id');
            $table->index('user_id');

            // Mencegah user yang sama terpasang dua kali ke divisi yang sama
            $table->unique([
                'company_division_id',
                'user_id',
            ], 'company_division_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_division_user');
    }
};
