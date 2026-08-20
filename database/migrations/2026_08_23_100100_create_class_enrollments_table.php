<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu student terdaftar di satu ClassSchedule, dibuat dari
     * halaman publik "Pilih Kelas" (ClassSelectionController::store()) setelah
     * hasil placement test-nya keluar.
     *
     * `form_submission_id` UNIQUE — satu submission cuma boleh punya 1
     * pendaftaran kelas (sesuai keputusan: 1 student pilih 1 kelas per
     * submission). Constraint ini juga jadi pengaman terakhir dari race
     * condition kalau ada 2 request submit nyaris bersamaan (lihat
     * ClassSelectionController::store()).
     */
    public function up(): void
    {
        if (Schema::hasTable('class_enrollments')) {
            return;
        }

        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('class_schedule_id', 36)->index();
            $table->char('student_id', 36)->index();
            $table->char('form_submission_id', 36)->unique();

            $table->enum('status', ['active', 'cancelled'])->default('active');

            $table->timestamps();

            $table->index(['class_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
    }
};
