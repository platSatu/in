<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = hasil placement test satu FormSubmission (1:1, makanya
     * form_submission_id unique). form_id ikut disimpan (denormalisasi) supaya
     * query "semua hasil form X" tidak perlu join ke form_submissions dulu.
     *
     * - mode: snapshot result_mode form ini pada saat hasil dibuat ('auto'/'manual').
     * - score: total skor (jumlah `score` opsi yang dipilih peserta di pertanyaan
     *   stage_group=placement_test). Cuma diisi kalau mode='auto'.
     * - summary_text: catatan/hasil bebas teks. Untuk mode='manual' ini yang diisi
     *   admin; untuk mode='auto' boleh kosong (skor sudah cukup).
     * - entered_by: id user admin yang input manual (null kalau mode='auto').
     * - whatsapp_sent_at: kapan WA hasil ini terkirim, null kalau belum/tidak
     *   terkirim (mis. form tidak mengaktifkan use_whatsapp_notification).
     */
    public function up(): void
    {
        // Guard: aman dijalankan meskipun tabelnya sudah ada duluan.
        if (Schema::hasTable('form_results')) {
            return;
        }

        Schema::create('form_results', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('form_submission_id', 36)->unique();
            $table->char('form_id', 36);

            $table->enum('mode', ['auto', 'manual']);
            $table->decimal('score', 8, 2)->nullable();
            $table->text('summary_text')->nullable();

            $table->char('entered_by', 36)->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();

            $table->timestamps();

            $table->index('form_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_results');
    }
};
