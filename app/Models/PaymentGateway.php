<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentGateway extends Model
{
    use HasUuids;

    protected $table = 'payment_gateways';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'gateway',
        'environment',
        'credentials',
        'is_active',
        'status',
        'expiry_minutes',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_active' => 'boolean',
        'expiry_minutes' => 'integer',
    ];

    /**
     * Gateway khusus Inayule (Buy Packages, Upgrade/Convert, Saldo Saya): selalu Midtrans,
     * terpisah dari gateway aktif (is_active) yang dipakai pendaftaran & quiz (Duitku).
     * Kredensial & mode sandbox/production tetap dari baris Midtrans di menu superadmin.
     */
    public static function forInayule(): ?self
    {
        return static::where('gateway', 'midtrans')->where('status', 'active')->latest('updated_at')->first();
    }

    /**
     * Daftar field kredensial yang dibutuhkan tiap gateway, dipakai bareng
     * oleh controller (validasi) dan view (render input dinamis).
     *
     * @return array<string, array<string, string>>
     */
    public static function credentialFields(): array
    {
        return [
            'duitku' => [
                'merchant_code' => 'Merchant Code',
                'secret_key' => 'Secret Key',
            ],
            'midtrans' => [
                'client_key' => 'API Key (Client Key)',
                'server_key' => 'Secret Key (Server Key)',
            ],
            'ipaymu' => [
                'va' => 'Virtual Account (VA)',
                'api_key' => 'API Key / Secret Key',
            ],
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
