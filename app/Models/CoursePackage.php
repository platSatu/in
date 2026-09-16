<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Katalog produk kursus siap jual (1 baris = 1 SKU): kombinasi CourseType +
 * CourseClass + CourseLevel + duration + price + credits. Ini yang nanti
 * dipilih end user di menu "beli paket" (topup saldo -> pilih package ->
 * dapat credits sejumlah kolom `credits` di baris ini).
 */
class CoursePackage extends Model
{
    use HasUuids;

    protected $table = 'course_packages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'course_type_id',
        'course_class_id',
        'course_level_id',
        'duration_value',
        'duration_unit',
        'price',
        'promo_price',
        'credits',
        'description',
        'status',
    ];

    protected $casts = [
        'duration_value' => 'integer',
        'price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'credits' => 'decimal:2',
    ];

    /**
     * FIX (16 September 2026, permintaan user): package "lagi promo" kalau
     * promo_price diisi DAN lebih kecil dari price aslinya (dijaga juga di
     * CoursePackageController lewat rule 'lt:price', ini jaring pengaman
     * kedua di sisi Model kalau ada baris lama/aneh di DB).
     */
    public function hasActivePromo(): bool
    {
        return $this->promo_price !== null && (float) $this->promo_price < (float) $this->price;
    }

    /**
     * Harga yang SEBENARNYA berlaku -- promo_price kalau lagi promo, price
     * biasa kalau tidak. Dipakai di tempat manapun butuh "harga jual
     * sekarang" tanpa perlu tahu logic promonya sendiri.
     */
    public function effectivePrice(): float
    {
        return $this->hasActivePromo() ? (float) $this->promo_price : (float) $this->price;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(CourseType::class, 'course_type_id');
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    public function level()
    {
        return $this->belongsTo(CourseLevel::class, 'course_level_id');
    }
}
