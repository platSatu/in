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
        Schema::create('company_branch', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36)->nullable();
            $table->char('company_profile_id', 36);

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();

            $table->text('address')->nullable();
            $table->string('handphone')->nullable();
            $table->string('email')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index('company_profile_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_branch');
    }
};
