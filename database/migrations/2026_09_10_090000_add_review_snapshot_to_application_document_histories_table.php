<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Simpan juga snapshot hasil review (review_status/review_note/reviewed_by/
| reviewed_at) versi LAMA ke sini pas dokumen di-upload ulang -- sebelumnya
| kolom-kolom ini cuma ada di application_documents (versi TERKINI), jadi
| begitu sebuah dokumen sudah direview lalu di-upload ulang (resubmit),
| hasil review versi lama ikut hilang ketimpa (balik ke pending lagi).
| Sekarang disalin dulu ke history di sini, supaya admin bisa lihat riwayat
| lengkap: versi mana yang pernah di-approve/reject, oleh siapa, kapan, dan
| catatannya apa (lihat diskusi "tracking history dokumen", 10 September
| 2026).
|
| Nullable semua -- baris history LAMA (sebelum migration ini) otomatis
| NULL di kolom-kolom baru, ditampilkan sebagai "belum pernah direview" di
| view, bukan error.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_document_histories', function (Blueprint $table) {
            $table->string('review_status')->nullable()->after('replaced_at');
            $table->text('review_note')->nullable()->after('review_status');
            $table->char('reviewed_by_user_id', 36)->nullable()->after('review_note');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('application_document_histories', function (Blueprint $table) {
            $table->dropColumn(['review_status', 'review_note', 'reviewed_by_user_id', 'reviewed_at']);
        });
    }
};
