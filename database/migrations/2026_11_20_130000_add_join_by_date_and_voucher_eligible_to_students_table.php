<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher Paket InaYule (20 November 2026):
 * - join_by_date    : tanggal murid bergabung di sistem lama, diisi lewat import Excel Student InaYule.
 * - voucher_eligible: switch "Bisa pakai voucher" per murid. Default off; murid dari Excel yang punya
 *                     join_by_date otomatis dinyalakan saat import, admin tetap bisa on/off per murid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'join_by_date')) {
                $table->date('join_by_date')->nullable();
            }
            if (! Schema::hasColumn('students', 'voucher_eligible')) {
                $table->boolean('voucher_eligible')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['join_by_date', 'voucher_eligible'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
