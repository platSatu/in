<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan Kode Referal INA YULE (25 November 2026), hanya 1 baris.
 * Admin cukup mengisi 1 angka: bonus saldo (Rp) untuk pemberi kode, 1x dari tiap murid baru yang lunas.
 * Murid baru tidak dapat diskon dari kode referal; kalau perlu diskon, admin memakai Voucher.
 */
class ReferralSetting extends Model
{
    protected $table = 'referral_settings';

    protected $fillable = [
        'is_active',
        'referrer_bonus_amount',
        'buyer_discount_percent',
        'referrer_commission_percent',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referrer_bonus_amount' => 'decimal:2',
        'buyer_discount_percent' => 'decimal:2',
        'referrer_commission_percent' => 'decimal:2',
    ];

    /** Pengaturan yang berlaku (dibuat otomatis dalam keadaan nonaktif kalau belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['is_active' => false]);
    }
}
