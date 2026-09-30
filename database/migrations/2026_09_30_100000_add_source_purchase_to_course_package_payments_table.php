<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade/convert paket sekarang menukar credit dari 1 baris pembelian yang
 * dipilih siswa (lihat App\Services\CoursePackagePayment\PackageUpgradeCalculator).
 * Kolom ini mencatat baris asal itu -- juga dipakai untuk mengunci credit-nya
 * selama pembayaran upgrade lewat gateway masih menunggu.
 *
 * Nama foreign key dibuat pendek (batas MySQL 64 karakter). Aman dijalankan
 * ulang: kolom & foreign key masing-masing hanya dibuat kalau belum ada.
 */
return new class extends Migration
{
    private const FOREIGN_KEY = 'cpp_source_purchase_fk';

    public function up(): void
    {
        if (! Schema::hasColumn('course_package_payments', 'source_course_package_purchase_id')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->uuid('source_course_package_purchase_id')->nullable()->after('trade_in_course_credit_id');
            });
        }

        if (! $this->hasForeignKey()) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->foreign('source_course_package_purchase_id', self::FOREIGN_KEY)
                    ->references('id')
                    ->on('course_package_purchases')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->hasForeignKey()) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->dropForeign(self::FOREIGN_KEY);
            });
        }

        if (Schema::hasColumn('course_package_payments', 'source_course_package_purchase_id')) {
            Schema::table('course_package_payments', function (Blueprint $table) {
                $table->dropColumn('source_course_package_purchase_id');
            });
        }
    }

    private function hasForeignKey(): bool
    {
        return collect(Schema::getForeignKeys('course_package_payments'))
            ->contains(fn (array $foreignKey) => $foreignKey['name'] === self::FOREIGN_KEY);
    }
};
