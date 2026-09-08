<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Daftar item checklist proses Aplikasi Kuliah, dikelompokkan per section:
| 'main' (Status Students: Registration Paid, dst), 'visa' (proses VISA),
| 'checkin' (Students Check-in). Data konfigurasi (diseed lewat
| ApplicationChecklistItemSeeder), bukan hardcode kolom per item -- supaya
| admin bisa tambah/ubah/urutkan item tanpa migration baru tiap kali.
|
| Tabel BARU, tidak menyentuh tabel/fitur lain yang sudah ada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('section', ['main', 'visa', 'checkin']);
            $table->string('code')->unique();
            $table->string('label');

            $table->boolean('requires_note')->default(false);
            $table->boolean('requires_photo')->default(false);
            $table->boolean('is_optional')->default(false);

            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_checklist_items');
    }
};
