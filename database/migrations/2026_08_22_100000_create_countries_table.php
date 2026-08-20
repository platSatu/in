<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Root entitas baru untuk hierarki University: Country -> City -> Major ->
| University -> University Profile -> University Album -> University Album Photo.
| Polanya sengaja disamakan persis dengan `cities`/`majors` (uuid id, user_id
| sebagai pemilik/creator, name, description, status) supaya konsisten dengan
| CRUD legacy yang sudah ada (AdminCrud + Auth::id() scoping).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
