<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu transaksi pembayaran gateway untuk fitur Apply Kampus -- terpisah
 * total dari FormPayment (dipakai Quiz Form), lihat migration
 * create_application_payments_table untuk penjelasan lengkap.
 *
 * Satu UniversityApplication bisa punya BEBERAPA baris di sini: 2 purpose
 * berbeda (registration_fee & departure_fee) + percobaan ulang kalau ada
 * transaksi sebelumnya expired/failed.
 */
class ApplicationPayment extends Model
{
    use HasUuids;

    protected $table = 'application_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    public const PURPOSE_REGISTRATION_FEE = 'registration_fee';
    public const PURPOSE_DEPARTURE_FEE = 'departure_fee';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'application_id',
        'purpose',
        'payment_gateway_id',
        'order_id',
        'gateway',
        'amount',
        'status',
        'payment_method',
        'payment_url',
        'gateway_reference',
        'raw_response',
        'raw_callback',
        'invoice_token',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'raw_response' => 'array',
        'raw_callback' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }
}
