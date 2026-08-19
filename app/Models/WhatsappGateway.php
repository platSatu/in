<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WhatsappGateway extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_gateways';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'gateway',
        'name',
        'api_host',
        'token',
        'secret_key',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Daftar pilihan gateway yang tersedia di dropdown. Baru ada 1 opsi karena
     * semua provider yang didukung sekarang punya prosedur pengiriman yang sama
     * (POST {api_host}/api/v2/send-message, header Authorization: token.secret_key).
     * Tambah key baru di sini kalau nanti ada provider dengan prosedur berbeda.
     *
     * @return array<string, string>
     */
    public static function gatewayOptions(): array
    {
        return [
            'whatsapp_gateway' => 'WhatsApp Gateway',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
