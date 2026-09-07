<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE LEVEL ===
| Master data level kemampuan, mis. "Beginner" / "Intermediate" / "Advanced".
| Dirujuk oleh `course_packages` lewat kolom `course_level_id`.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_level', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_level');
    }
};
