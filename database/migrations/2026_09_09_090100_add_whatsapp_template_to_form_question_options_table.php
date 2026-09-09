<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === TEMPLATE WHATSAPP PER OPSI (FITUR TAMBAHAN) ===
     *
     * Selain whatsapp_template_id di tabel forms (1 template, 1 pesan per
     * submission -- TIDAK berubah sama sekali), sekarang tiap OPSI jawaban
     * (form_question_options) boleh punya template WA sendiri, opsional.
     *
     * Kalau peserta memilih sebuah opsi yang whatsapp_template_id-nya diisi,
     * satu pesan WA TERPISAH dikirim khusus untuk opsi itu, di LUAR pesan
     * per-form yang sudah ada (dua-duanya jalan, tidak saling menggantikan).
     * Kalau soal itu multiple choice (checkbox) dan peserta memilih beberapa
     * opsi yang masing-masing punya template, pesan dikirim satu per satu
     * (satu pesan per opsi yang cocok), bukan digabung jadi satu pesan.
     *
     * Nullable, tanpa foreign key constraint -- mengikuti pola PERSIS
     * whatsapp_template_id di migration add_whatsapp_notification_to_forms_table
     * supaya konsisten dengan kolom sejenis yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('form_question_options', function (Blueprint $table) {
            if (!Schema::hasColumn('form_question_options', 'whatsapp_template_id')) {
                $table->char('whatsapp_template_id', 36)->nullable()->after('is_correct');
                $table->index('whatsapp_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_question_options', function (Blueprint $table) {
            if (Schema::hasColumn('form_question_options', 'whatsapp_template_id')) {
                $table->dropIndex(['whatsapp_template_id']);
            }

            if (Schema::hasColumn('form_question_options', 'whatsapp_template_id')) {
                $table->dropColumn('whatsapp_template_id');
            }
        });
    }
};
