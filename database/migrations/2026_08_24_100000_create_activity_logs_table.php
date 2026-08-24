<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log aktivitas admin (bukan peserta quiz publik — lihat catatan di
     * App\Helpers\ActivityLogger). Ditulis OTOMATIS lewat event Eloquent global
     * (App\Providers\AppServiceProvider::boot()), jadi menutup SEMUA modul yang
     * sudah ada maupun yang ditambah nanti — bukan cuma yang lewat AdminCrud,
     * termasuk juga insert langsung di dalam loop (mis. batch simpan pertanyaan
     * di FormQuestionController::store()).
     *
     * actor_name/actor_email SENGAJA disalin ke sini (bukan cuma actor_id lalu
     * join ke tabel users), supaya histori tetap akurat walau nama/email user
     * itu diganti belakangan, atau bahkan user-nya sendiri sudah dihapus —
     * catatan histori tidak boleh berubah/hilang cuma karena data user saat ini
     * berubah.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Siapa yang melakukan (snapshot, lihat catatan di atas).
            $table->char('actor_id', 36)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();

            // 'login', 'logout', 'created', 'updated', 'deleted'.
            $table->string('event', 20);

            // Apa yang kena aksi: nama model (mis. "FormQuestion") + id barisnya
            // + label singkat yang enak dibaca (mis. isi question_text) supaya
            // tabel index bisa langsung menampilkan konteks tanpa buka detail.
            $table->string('subject_type')->nullable();
            $table->char('subject_id', 36)->nullable();
            $table->string('subject_label')->nullable();

            // Ringkasan 1 baris, mis. "membuat Form Question", "login ke sistem".
            $table->string('description');

            // Snapshot data SEBELUM & SESUDAH aksi (JSON), password/token dsb
            // sudah disamarkan sebelum disimpan — lihat ActivityLogger::redact().
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index('actor_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
