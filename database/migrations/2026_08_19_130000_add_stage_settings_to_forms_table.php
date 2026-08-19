<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === STAGE "DATA PRIBADI" & MODE HASIL ===
     * Form sekarang bisa dipakai untuk alur: Registrasi -> (opsional) Data Pribadi ->
     * (opsional) Payment -> Placement Test -> Hasil. Dua kolom ini yang jadi saklar:
     *
     * - has_personal_data_stage: kalau true, wizard publik menampilkan step tambahan
     *   "Data Pribadi" sebelum step Payment/Placement Test, berisi pertanyaan yang
     *   ditandai stage_group=personal_data (lihat migration form_questions). Kalau
     *   form ini requires_payment juga, payment SELALU diposisikan setelah step Data
     *   Pribadi dan sebelum Placement Test (payment_position diabaikan khusus untuk
     *   kombinasi ini, supaya alur "isi data -> bayar -> tes" konsisten).
     *
     * - result_mode: menentukan bagaimana "hasil" placement test ditentukan.
     *     'none'   = tidak ada tahap hasil sama sekali (perilaku form biasa).
     *     'auto'   = dihitung otomatis dari total `score` opsi yang dipilih peserta
     *                (lihat form_question_options.score) begitu submit selesai.
     *     'manual' = admin yang input hasil (bebas teks) belakangan lewat halaman
     *                submission, sistem tidak menghitung apa pun sendiri.
     *   Terlepas dari mode-nya, notifikasi WhatsApp (kalau use_whatsapp_notification
     *   aktif) baru terkirim setelah hasil benar-benar ada (otomatis dihitung, atau
     *   admin selesai input manual).
     */
    public function up(): void
    {
        // Guard per kolom, sama seperti migration lain di batch ini.
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'has_personal_data_stage')) {
                $table->boolean('has_personal_data_stage')->default(false)->after('use_whatsapp_notification');
            }

            if (!Schema::hasColumn('forms', 'result_mode')) {
                $table->enum('result_mode', ['none', 'auto', 'manual'])->default('none')->after('has_personal_data_stage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $columns = array_filter(
                ['has_personal_data_stage', 'result_mode'],
                fn ($column) => Schema::hasColumn('forms', $column)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
