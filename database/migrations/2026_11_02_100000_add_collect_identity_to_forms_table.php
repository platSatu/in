<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === MINTA IDENTITAS PESERTA (Nama/Email/WhatsApp) ===
     * collect_identity: default TRUE = perilaku SEBELUMNYA (step Nama/Email/HP selalu tampil),
     * jadi semua form yang sudah berjalan tidak berubah. Kalau admin mematikannya untuk satu
     * form, step itu tidak dirender sama sekali: peserta langsung ke pertanyaan, submission
     * tersimpan tanpa Student (form_submissions.user_id memang nullable sejak awal).
     * Kombinasi dengan pembayaran / notifikasi WhatsApp ditolak di FormController karena
     * keduanya butuh nama/email/nomor peserta.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'collect_identity')) {
                $table->boolean('collect_identity')->default(true)->after('has_personal_data_stage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'collect_identity')) {
                $table->dropColumn('collect_identity');
            }
        });
    }
};
