<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 pemakaian kode referal oleh 1 pembeli baru (25 November 2026). Pola sama dengan
 * CourseVoucherRedemption: baris tidak dihapus, pembayaran gagal/kedaluwarsa cukup tidak dihitung lagi.
 */
class ReferralRedemption extends Model
{
    use HasUuids;

    protected $table = 'referral_redemptions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'referrer_user_id',
        'buyer_user_id',
        'buyer_student_id',
        'course_package_id',
        'course_package_payment_id',
        'price_before',
        'buyer_discount_percent',
        'discount_amount',
        'price_after',
        'referrer_commission_percent',
        'referrer_bonus_amount',
        'commission_amount',
        'commission_deposit_id',
        'commission_paid_at',
    ];

    protected $casts = [
        'price_before' => 'decimal:2',
        'buyer_discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'price_after' => 'decimal:2',
        'referrer_commission_percent' => 'decimal:2',
        'referrer_bonus_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_paid_at' => 'datetime',
    ];

    /** Pemakaian yang masih berlaku: lunas, menunggu bayar (belum kedaluwarsa), atau checkout yang baru berjalan. */
    public static function applyActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('payment', fn (Builder $p) => $p->where('status', CoursePackagePayment::STATUS_PAID))
                ->orWhereHas('payment', fn (Builder $p) => $p->where('status', CoursePackagePayment::STATUS_PENDING)
                    ->where(fn (Builder $e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->orWhere(fn (Builder $n) => $n->whereNull('course_package_payment_id')
                    ->where('created_at', '>', now()->subMinutes(CourseVoucherRedemption::UNATTACHED_GRACE_MINUTES)));
        });
    }

    public function statusLabel(): string
    {
        $payment = $this->payment;

        return match (true) {
            $this->commission_paid_at !== null => 'Lunas, bonus masuk',
            $payment?->status === CoursePackagePayment::STATUS_PAID => 'Lunas',
            $payment?->status === CoursePackagePayment::STATUS_PENDING && (! $payment->expires_at || $payment->expires_at->isFuture()) => 'Menunggu bayar',
            $payment === null && $this->created_at?->gt(now()->subMinutes(CourseVoucherRedemption::UNATTACHED_GRACE_MINUTES)) => 'Diproses',
            default => 'Batal',
        };
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CoursePackagePayment::class, 'course_package_payment_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }
}
