<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * === BATAS PESERTA QUIZ ===
     * max_participants: nullable. Kosong = tanpa batas (form lama tidak berubah sama sekali;
     * kalau mau dibatasi, admin tinggal edit form-nya). Kalau diisi, form otomatis tertutup
     * begitu jumlah peserta (student berbeda yang sudah submit) mencapai angka ini --
     * URL-nya tetap bisa dibuka, tapi yang tampil pesan "kuota penuh", bukan form.
     *
     * Indeks (form_id, user_id) supaya hitung peserta (count distinct user_id per form)
     * tetap cepat walau submission sudah banyak.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'max_participants')) {
                $table->unsignedInteger('max_participants')->nullable()->after('end_date');
            }
        });

        if (!Schema::hasIndex('form_submissions', ['form_id', 'user_id'])) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->index(['form_id', 'user_id'], 'form_submissions_form_user_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('form_submissions', 'form_submissions_form_user_index')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropIndex('form_submissions_form_user_index');
            });
        }

        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'max_participants')) {
                $table->dropColumn('max_participants');
            }
        });
    }
};
