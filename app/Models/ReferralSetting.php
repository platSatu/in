<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan Kode Referal INA YULE (25 November 2026), hanya 1 baris.
 * Contoh 10% + 10%: pembeli baru dapat diskon 10%, pemberi kode dapat komisi saldo 10%
 * dari yang benar-benar dibayar pembeli.
 */
class ReferralSetting extends Model
{
    protected $table = 'referral_settings';

    protected $fillable = [
        'is_active',
        'buyer_discount_percent',
        'referrer_commission_percent',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'buyer_discount_percent' => 'decimal:2',
        'referrer_commission_percent' => 'decimal:2',
    ];

    /** Pengaturan yang berlaku (dibuat otomatis dalam keadaan nonaktif kalau belum ada). */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['is_active' => false]);
    }
}
