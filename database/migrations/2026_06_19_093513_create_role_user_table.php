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
        Schema::create('role_user', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);
            $table->char('role_id', 36);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            // Optional indexes
            $table->index('user_id');
            $table->index('role_id');

            // Mencegah role yang sama terpasang dua kali ke user yang sama
            $table->unique([
                'user_id',
                'role_id',
            ], 'role_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
