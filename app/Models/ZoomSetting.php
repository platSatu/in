<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kredensial Server-to-Server OAuth Zoom (menu Settings > Setting Zoom).
 * Lihat migration create_zoom_settings_table untuk penjelasan kolom &
 * App\Services\Zoom\ZoomClient untuk cara kredensial ini dipakai memanggil
 * Zoom API.
 */
class ZoomSetting extends Model
{
    use HasUuids;

    protected $table = 'zoom_settings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'account_id',
        'client_id',
        'client_secret',
        'secret_token',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // Ter-enkripsi otomatis di database (Laravel encrypted cast) --
        // kredensial ini memberi akses penuh ke akun Zoom terkait, jadi
        // sengaja tidak disimpan plain text seperti payment_gateways/
        // whatsapp_gateways.
        'account_id' => 'encrypted',
        'client_id' => 'encrypted',
        'client_secret' => 'encrypted',
        'secret_token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Konfigurasi Zoom yang sedang dipakai sistem -- lihat catatan
     * deactivateOthers() di ZoomSettingController, cuma 1 baris yang boleh
     * is_active=true dalam satu waktu.
     */
    public static function activeSetting(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();
    }
}
