<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 4 "Konversi/Upgrade Paket" (16 September 2026) -- 2 kolom baru di
 * course_package_payments supaya 1 baris checkout bisa merekam PORSI harga
 * package baru yang ditutup dari trade-in saldo credit lama (bukan dari
 * Deposit/gateway), sejajar dengan deposit_portion & gateway_portion yang
 * sudah ada:
 *
 * - credit_trade_in_portion: nilai rupiah trade-in yang benar-benar dipakai
 *   menutup harga package baru (lihat App\Services\CoursePackagePayment\
 *   PackageUpgradeCalculator) -- BISA lebih kecil dari total nilai trade-in
 *   kalau ternyata nilainya melebihi harga package baru (sisanya masuk
 *   saldo Deposit, lihat trade_in_leftover di preview()).
 * - trade_in_course_credit_id: FK ke baris CourseCredit (source_type=
 *   TRADE_IN_DEBIT, lihat App\Services\CourseCredit\CourseCreditDebitService
 *   ::debitEntireBalance()) yang jadi bukti/jejak audit "credit lama yang
 *   mana persis yang ditukar" -- pola sama dengan
 *   class_sessions.course_credit_id (Fase 2).
 *
 * TIDAK perlu mengubah course_package_purchases.source di sini -- kolom itu
 * sudah VARCHAR(30) bebas nilai sejak migration
 * extend_course_package_purchase_source_and_wa_template (16 September
 * 2026), jadi nilai baru 'upgrade_purchase' (lihat
 * CoursePackagePurchase::SOURCE_UPGRADE_PURCHASE) tidak butuh migration
 * tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_package_payments', 'credit_trade_in_portion')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->decimal('credit_trade_in_portion', 12, 2)->default(0)->after('gateway_portion');
            });
        }

        if (!Schema::hasColumn('course_package_payments', 'trade_in_course_credit_id')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->foreignUuid('trade_in_course_credit_id')
                    ->nullable()
                    ->after('credit_trade_in_portion')
                    ->constrained('course_credits')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_package_payments', 'trade_in_course_credit_id')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('trade_in_course_credit_id');
            });
        }

        if (Schema::hasColumn('course_package_payments', 'credit_trade_in_portion')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->dropColumn('credit_trade_in_portion');
            });
        }
    }
};
