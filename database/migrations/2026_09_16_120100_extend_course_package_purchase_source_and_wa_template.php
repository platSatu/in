<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2 perubahan kecil buat fitur checkout package berbayar (deposit / gateway
 * / campuran):
 *
 * 1. course_package_purchases.source ditambah 2 nilai baru: 'gateway_purchase'
 *    (100% dibayar lewat payment gateway, tidak pakai saldo Deposit sama
 *    sekali) & 'mixed_purchase' (sebagian saldo Deposit + sisanya gateway).
 *    Nilai lama ('trial_claim', 'deposit_purchase') TETAP dipakai persis
 *    seperti sebelumnya -- 'deposit_purchase' sekarang juga dipakai untuk
 *    checkout yang 100% tertutup saldo Deposit (bukan cuma trial), sesuai
 *    makna aslinya di docblock migration create_course_package_purchases_table.
 *    Pakai raw ALTER (bukan Blueprint::enum, biar aman kalau kolomnya ternyata
 *    bukan native ENUM di sisi DB) supaya tidak butuh doctrine/dbal.
 *
 * 2. whatsapp_templates ditambah 1 kolom boolean
 *    is_course_package_purchase_template -- pola "1 baris aktif, deactivate
 *    others on save" SAMA PERSIS dengan PaymentGateway.is_active /
 *    WhatsappGateway.is_active (lihat
 *    App\Http\Controllers\Settings\PaymentGatewayController::deactivateOthers()),
 *    dipakai App\Http\Controllers\StudentPortal\InaYulePackageWebhookController
 *    untuk tahu template WA mana yang harus dipakai saat kirim notifikasi
 *    pembelian package berhasil.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE course_package_purchases MODIFY source VARCHAR(30) NOT NULL DEFAULT 'trial_claim'");

        if (!Schema::hasColumn('whatsapp_templates', 'is_course_package_purchase_template')) {
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                $table->boolean('is_course_package_purchase_template')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_templates', 'is_course_package_purchase_template')) {
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                $table->dropColumn('is_course_package_purchase_template');
            });
        }
    }
};
