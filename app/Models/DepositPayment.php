<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak 1 percobaan transaksi topup saldo lewat payment gateway. Ledger
 * saldo yang SEBENARNYA tetap di Deposit + Transaction (dibuat oleh
 * App\Http\Controllers\Dashboard\DepositWebhookController begitu baris ini
 * dikonfirmasi 'paid' oleh gateway) -- lihat docblock migration
 * create_deposit_payments_table.
 */
class DepositPayment extends Model
{
    use HasUuids;

    protected $table = 'deposit_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'payment_gateway_id',
        'order_id',
        'gateway',
        'name',
        'email',
        'handphone',
        'amount',
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
        'amount' => 'decimal:2',
        'raw_response' => 'array',
        'raw_callback' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
