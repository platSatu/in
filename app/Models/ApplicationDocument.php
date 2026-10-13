<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * File dokumen TERKINI untuk satu (Aplikasi, Jenis Dokumen). Versi
 * sebelumnya (kalau pernah upload ulang) ada di histories(), BUKAN di sini.
 */
class ApplicationDocument extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const REVIEW_PENDING = 'pending';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REJECTED = 'rejected';

    protected $fillable = [
        'application_id',
        'document_type_id',
        // Nama file untuk jenis yang boleh banyak (mis. nama sertifikat).
        'title',
        'file_path',
        'original_filename',
        'uploaded_by_user_id',
        'uploaded_at',
        'review_status',
        'review_note',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** Student boleh menghapus sendiri selama belum di-approve (jenis yang boleh banyak). */
    public function canBeDeletedByStudent(): bool
    {
        return in_array($this->review_status, [self::REVIEW_PENDING, self::REVIEW_REJECTED], true);
    }

    /** Label tampilan: "Any Certificate: Juara 1 Lomba" kalau ada title. */
    public function displayLabel(): string
    {
        $label = $this->documentType->label ?? 'Document';

        return filled($this->title) ? "{$label}: {$this->title}" : $label;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApplicationDocumentHistory::class, 'application_document_id')
            ->orderByDesc('replaced_at');
    }
}
