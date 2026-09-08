<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu item checklist proses Aplikasi Kuliah (mis. "Registration Paid",
 * "Apply Online" di section VISA, dst) -- data konfigurasi, lihat
 * ApplicationChecklistItemSeeder. Status per-aplikasi ada di
 * ApplicationChecklistEntry, BUKAN di sini.
 */
class ApplicationChecklistItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const SECTION_MAIN = 'main';
    public const SECTION_VISA = 'visa';
    public const SECTION_CHECKIN = 'checkin';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'section',
        'code',
        'label',
        'requires_note',
        'requires_photo',
        'is_optional',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'requires_note' => 'boolean',
        'requires_photo' => 'boolean',
        'is_optional' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(ApplicationChecklistEntry::class, 'checklist_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
