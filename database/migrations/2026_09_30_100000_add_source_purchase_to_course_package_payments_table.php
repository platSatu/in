<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade/convert paket sekarang menukar credit dari 1 baris pembelian yang
 * dipilih siswa (lihat App\Services\CoursePackagePayment\PackageUpgradeCalculator).
 * Kolom ini mencatat baris asal itu -- juga dipakai untuk mengunci credit-nya
 * selama pembayaran upgrade lewat gateway masih menunggu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_package_payments', 'source_course_package_purchase_id')) {
            return;
        }

        Schema::table('course_package_payments', function (Blueprint $table) {
            $table->foreignUuid('source_course_package_purchase_id')
                ->nullable()
                ->after('trade_in_course_credit_id')
                ->constrained('course_package_purchases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_package_payments', 'source_course_package_purchase_id')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('source_course_package_purchase_id');
            });
        }
    }
};
