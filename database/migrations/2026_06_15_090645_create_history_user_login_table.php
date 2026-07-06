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
        Schema::create('history_user_login', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('user_id', 36);

            $table->dateTime('last_login')->nullable();
            $table->dateTime('last_logout')->nullable();

            // durasi dalam detik
            $table->unsignedBigInteger('duration')->nullable();

            $table->timestamps();

            // optional: foreign key (kalau tabel users pakai uuid)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_user_login');
    }
};
