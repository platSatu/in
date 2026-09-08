<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jenis dokumen yang bisa diupload calon siswa per Aplikasi Kuliah
 * (Passport, Transcript, dst) -- data konfigurasi, lihat DocumentTypeSeeder.
 */
class DocumentType extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'code',
        'label',
        'group_label',
        'allowed_extensions',
        'is_required',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Daftar ekstensi yang diizinkan sebagai array (mis. ['jpg','jpeg']),
     * dipecah dari kolom CSV allowed_extensions.
     */
    public function allowedExtensionsArray(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->allowed_extensions)));
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'document_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
