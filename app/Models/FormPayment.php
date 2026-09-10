<?php

namespace App\Models;

use App\Services\Payment\Contracts\Payable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormPayment extends Model implements Payable
{
    use HasUuids;

    protected $table = 'form_payments';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'form_id',
        'payment_gateway_id',
        'form_submission_id',
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

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    // FIX (10 September 2026): implementasi interface Payable -- lihat
    // docblock lengkap di App\Services\Payment\Contracts\Payable. Semua
    // method di bawah cuma MEMBUNGKUS field yang sudah ada, PERSIS nilai
    // yang dulu diakses langsung oleh DuitkuGateway/MidtransGateway/
    // IpaymuGateway -- tidak ada perilaku FormPayment yang berubah.
    public function getOrderId(): string
    {
        return (string) $this->order_id;
    }

    public function getAmount(): int
    {
        return (int) round((float) $this->amount);
    }

    public function getPayerName(): string
    {
        return (string) $this->name;
    }

    public function getPayerEmail(): string
    {
        return (string) $this->email;
    }

    public function getPayerPhone(): string
    {
        return (string) $this->handphone;
    }

    public function getDescription(): string
    {
        return 'Pembayaran ' . ($this->form->name ?? 'Form');
    }

    public function getReturnUrl(): string
    {
        return route('frontend.payment.return', ['order_id' => $this->order_id]);
    }
}
