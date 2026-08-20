<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu slot jadwal kelas kursus milik sebuah Branch (nama
     * kelas, level, tanggal + jam, dan kapasitas peserta). Diisi admin lewat
     * menu Quiz > Class Schedule, lalu ditawarkan ke student lewat link
     * "Pilih Kelas" yang disisipkan ke pesan WhatsApp hasil placement test
     * (lihat ClassSchedule::existsActiveForBranch(), dipakai di
     * FrontendController::finalizeCompletedSubmission() untuk mode auto, dan
     * FormController::saveResult() untuk mode manual).
     *
     * `level` sengaja teks bebas (bukan terhubung otomatis ke skor placement
     * test) — admin yang menentukan levelnya, sesuai keputusan awal fitur ini.
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun tabelnya sudah ada duluan (pola yang
        // sama dengan migration create_form_results_table).
        if (Schema::hasTable('class_schedules')) {
            return;
        }

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Admin pemilik (dipakai AdminCrud untuk scoping index/edit/delete),
            // sama polanya dengan tabel-tabel admin CRUD lain di project ini.
            $table->char('user_id', 36)->nullable()->index();
            $table->char('branch_id', 36)->index();

            $table->string('name');
            $table->string('level')->nullable();
            $table->date('class_date');
            $table->time('start_time')->nullable();
            $table->unsignedInteger('capacity')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
