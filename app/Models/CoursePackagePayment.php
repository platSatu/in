<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak 1 percobaan checkout pembelian package berbayar (deposit / gateway /
 * campuran) -- ledger saldo yang SEBENARNYA (Deposit + CourseCredit +
 * CoursePackagePurchase) HANYA dibuat oleh
 * App\Http\Controllers\StudentPortal\InaYulePackageWebhookController (untuk
 * porsi gateway) atau langsung di 1 DB transaction di
 * InaYulePackageCheckoutController::store() (kalau 100% tertutup saldo
 * Deposit) -- lihat docblock migration create_course_package_payments_table.
 */
class CoursePackagePayment extends Model
{
    use HasUuids;

    protected $table = 'course_package_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'user_id',
        'course_package_id',
        'payment_gateway_id',
        'course_package_purchase_id',
        'order_id',
        'gateway',
        'name',
        'email',
        'handphone',
        'price_total',
        'deposit_portion',
        'gateway_portion',
        'credit_trade_in_portion',
        'trade_in_course_credit_id',
        'credits_granted',
        'status',
        'payment_method',
        'payment_url',
        'gateway_reference',
        'raw_response',
        'raw_callback',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'price_total' => 'decimal:2',
        'deposit_portion' => 'decimal:2',
        'gateway_portion' => 'decimal:2',
        'credit_trade_in_portion' => 'decimal:2',
        'credits_granted' => 'decimal:2',
        'raw_response' => 'array',
        'raw_callback' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(CoursePackagePurchase::class, 'course_package_purchase_id');
    }

    /**
     * FASE 4 "Konversi/Upgrade Paket" -- baris CourseCredit (source_type=
     * TRADE_IN_DEBIT) yang jadi bukti "credit lama yang mana persis yang
     * ditukar" untuk checkout upgrade ini. Null kalau checkout ini BUKAN
     * upgrade (pembelian package biasa, tidak ada trade-in).
     */
    public function tradeInCourseCredit(): BelongsTo
    {
        return $this->belongsTo(CourseCredit::class, 'trade_in_course_credit_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * True kalau checkout ini tidak butuh gateway sama sekali (100% tertutup
     * saldo Deposit) -- dipakai InaYulePackageCheckoutController::store()
     * untuk memutuskan jalur instan vs jalur "tunggu webhook".
     */
    public function isDepositOnly(): bool
    {
        return (float) $this->gateway_portion <= 0.0;
    }
}
