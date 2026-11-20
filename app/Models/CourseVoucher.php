<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Voucher diskon pembelian paket credit InaYule (20 November 2026), menu Course > Voucher Paket.
 * Terpisah dari App\Models\Voucher (sistem paket/deposit lama). Aturan pemakaian ada di
 * App\Services\CourseVoucher\CourseVoucherService.
 */
class CourseVoucher extends Model
{
    use HasUuids;

    protected $table = 'course_vouchers';

    protected $keyType = 'string';

    public $incrementing = false;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_NOMINAL = 'nominal';

    public const TYPES = [
        self::TYPE_PERCENT => 'Persentase (%)',
        self::TYPE_NOMINAL => 'Nominal (Rp)',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'quota',
        'valid_from',
        'valid_until',
        'only_eligible_students',
        'course_package_ids',
        'status',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'quota' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'only_eligible_students' => 'boolean',
        'course_package_ids' => 'array',
    ];

    /** Kode selalu huruf besar tanpa spasi, supaya "promo10" & "PROMO10" dianggap sama. */
    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CourseVoucherRedemption::class, 'course_voucher_id');
    }

    /** Pemakaian yang masih menghitung kuota (lunas, atau menunggu bayar & belum kedaluwarsa). */
    public function activeRedemptionCount(): int
    {
        return CourseVoucherRedemption::applyActive($this->redemptions()->getQuery())->count();
    }

    public function appliesToPackage(string $packageId): bool
    {
        return empty($this->course_package_ids) || in_array($packageId, $this->course_package_ids, true);
    }

    /** Potongan (Rupiah, dibulatkan ke bawah) untuk harga ini. */
    public function discountFor(float $price): float
    {
        $discount = $this->discount_type === self::TYPE_PERCENT
            ? $price * ((float) $this->discount_value) / 100
            : (float) $this->discount_value;

        return (float) floor(max(0, $discount));
    }

    /** "10%" atau "Rp 50.000". */
    public function discountLabel(): string
    {
        return $this->discount_type === self::TYPE_PERCENT
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2, ',', '.'), '0'), ',').'%'
            : 'Rp '.number_format((float) $this->discount_value, 0, ',', '.');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
