<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('field_of_study')->nullable();
            $table->integer('min_budget')->nullable();
            $table->integer('max_budget')->nullable();
            $table->string('preferred_language')->nullable();
            $table->boolean('scholarship_needed')->default(false);
            $table->string('country')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
