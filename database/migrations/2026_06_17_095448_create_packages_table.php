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
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->char('application_category_id', 36)->index();

            $table->string('name');

            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->decimal('price', 15, 2);

            $table->integer('duration_days');

            $table->timestamps();

            // optional foreign key kalau user pakai UUID
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
