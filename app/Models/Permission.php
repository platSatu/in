<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuids;

    protected $table = 'permissions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'label',
        'icon',
        'group_label',
        'sort_order',
    ];

    /**
     * Role-role yang punya akses ke permission (modul/menu) ini.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id')
            ->withPivot('can_edit')
            ->withTimestamps();
    }

    /**
     * Sinkronkan katalog permission dari config/menu.php ke tabel ini
     * (upsert berdasarkan 'key' — aman dipanggil berkali-kali/idempoten).
     * Dipakai saat migration awal & lewat `php artisan permissions:sync`
     * setiap kali config/menu.php ditambah entry menu baru.
     */
    public static function syncFromRegistry(): void
    {
        foreach (config('menu', []) as $index => $item) {
            if (empty($item['key'])) {
                continue;
            }

            static::updateOrCreate(
                ['key' => $item['key']],
                [
                    'label' => $item['label'] ?? $item['key'],
                    'icon' => $item['icon'] ?? null,
                    'group_label' => $item['group'] ?? null,
                    'sort_order' => $item['sort'] ?? $index,
                ]
            );
        }
    }
}
