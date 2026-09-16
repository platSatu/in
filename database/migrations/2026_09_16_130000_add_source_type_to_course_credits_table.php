<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 -- "Fondasi Pelacakan Asal Credit" (16 September 2026, hasil diskusi
 * panjang soal Konversi/Upgrade Paket & Honor Pengajar): kolom ini menandai
 * KENAPA 1 baris ledger course_credits itu ada, tanpa perlu menebak dari teks
 * bebas di kolom `description`.
 *
 * 'purchase'          = credit MASUK dari klaim trial / beli package (baris
 *                        `kredit`, sudah berjalan sekarang lewat
 *                        InaYulePackageController::claimTrial(),
 *                        InaYulePackageCheckoutController::completeWithDepositOnly(),
 *                        & InaYulePackageWebhookController) -- dipakai juga
 *                        sebagai DEFAULT supaya baris lama yang sudah ada
 *                        (dibuat sebelum kolom ini ada) otomatis tertandai
 *                        benar tanpa perlu backfill manual.
 * 'session_debit'      = credit KELUAR karena dipakai untuk sesi kelas
 *                        (fitur Jadwal+Absensi, MASIH tahap desain, belum
 *                        dibangun -- kolom disiapkan sekarang supaya tidak
 *                        perlu migration tambahan lagi nanti).
 * 'trade_in_debit'     = credit KELUAR karena "ditukar" saat upgrade/konversi
 *                        ke package lain (fitur Konversi Paket, MASIH tahap
 *                        desain, belum dibangun -- sama alasannya).
 *
 * Sengaja VARCHAR(30) polos (bukan native ENUM) -- ikut pola yang sudah
 * dipakai migration extend_course_package_purchase_source_and_wa_template
 * (course_package_purchases.source) supaya menambah nilai baru nanti cukup
 * migration kecil tanpa perlu doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_credits', 'source_type')) {
            Schema::table('course_credits', function (Blueprint $table) {
                $table->string('source_type', 30)->default('purchase')->after('course_package_purchase_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_credits', 'source_type')) {
            Schema::table('course_credits', function (Blueprint $table) {
                $table->dropColumn('source_type');
            });
        }
    }
};
