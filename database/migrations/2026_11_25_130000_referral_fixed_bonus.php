<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kode Referal jadi bonus rupiah tetap (25 November 2026).
 * Murid baru tidak lagi dapat diskon dari kode referal (diskon cukup lewat Voucher). Pemberi kode dapat
 * bonus saldo sejumlah rupiah yang diisi admin, 1x dari tiap murid baru yang pembayarannya lunas.
 * Kolom persen lama dibiarkan (riwayat lama tetap terbaca) dan diisi 0 untuk pemakaian baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('referral_settings', 'referrer_bonus_amount')) {
            Schema::table('referral_settings', function (Blueprint $table) {
                $table->decimal('referrer_bonus_amount', 14, 2)->default(0)->after('is_active');
            });
        }

        if (! Schema::hasColumn('referral_redemptions', 'referrer_bonus_amount')) {
            Schema::table('referral_redemptions', function (Blueprint $table) {
                $table->decimal('referrer_bonus_amount', 14, 2)->nullable()->after('referrer_commission_percent');
            });
        }

        DB::table('referral_settings')->update(['buyer_discount_percent' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('referral_redemptions', 'referrer_bonus_amount')) {
            Schema::table('referral_redemptions', fn (Blueprint $table) => $table->dropColumn('referrer_bonus_amount'));
        }
        if (Schema::hasColumn('referral_settings', 'referrer_bonus_amount')) {
            Schema::table('referral_settings', fn (Blueprint $table) => $table->dropColumn('referrer_bonus_amount'));
        }
    }
};
