<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apply beberapa kampus sekaligus (cart "Pilihan Kampus", 12 November 2026): satu transaksi gateway
 * membayar Registration Fee beberapa aplikasi. Tiap aplikasi tetap punya baris pembayarannya sendiri
 * (nominal bagiannya), disatukan lewat group_order_id = order_id transaksi yang dikirim ke gateway.
 *
 * Sekalian: label Guardian Letter mengikuti Google Sheet "Requirements University" (di bawah 18 tahun).
 * Hanya label, 'code' tidak disentuh supaya file yang sudah diupload tetap terhubung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_payments', function (Blueprint $table) {
            $table->string('group_order_id')->nullable()->after('order_id')->index();
        });

        DB::table('document_types')->where('code', 'guardian_letter')
            ->update(['label' => 'Guardian Letter (if student under 18 years old)', 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('application_payments', function (Blueprint $table) {
            $table->dropIndex(['group_order_id']);
            $table->dropColumn('group_order_id');
        });

        DB::table('document_types')->where('code', 'guardian_letter')
            ->update(['label' => 'Guardian Letter (if student under 17 years old)', 'updated_at' => now()]);
    }
};
