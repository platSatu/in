<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| === COURSE CLASS ===
| Master data program/subjek kursus, mis. "Chinese Adult", "Chinese Kids",
| "Conversation Master Class". Dirujuk oleh `course_packages` lewat kolom
| `course_class_id`. Independen dari `course_type` & `course_level` (bukan
| relasi parent-child, cuma sama-sama dimensi katalog).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_class', function (Blueprint $table) {
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
        Schema::dropIfExists('course_class');
    }
};
