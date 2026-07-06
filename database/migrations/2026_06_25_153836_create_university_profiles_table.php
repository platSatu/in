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
        Schema::create('university_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);
            $table->char('university_id', 36);

            $table->string('field'); // contoh: IT, Medicine, Business

            $table->unsignedBigInteger('min_budget')->nullable();
            $table->unsignedBigInteger('max_budget')->nullable();

            $table->string('language')->nullable(); 
            // contoh: english, mandarin, indonesia

            $table->boolean('scholarship_available')->default(false);

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index('user_id');
            $table->index('university_id');
            $table->index('field');
            $table->index(['university_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_profiles');
    }
};
