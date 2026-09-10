<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu versi LAMA dari sebuah ApplicationDocument, disalin ke sini
 * tepat sebelum baris ApplicationDocument-nya ditimpa file baru (upload
 * ulang) -- supaya versi lama tetap bisa dilihat/dibandingkan admin.
 */
class ApplicationDocumentHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'application_document_id',
        'file_path',
        'original_filename',
        'uploaded_by_user_id',
        'uploaded_at',
        'replaced_at',
        // Snapshot hasil review versi LAMA ini (sebelum ditimpa upload ulang)
        // -- lihat migration add_review_snapshot_to_application_document_histories_table.
        'review_status',
        'review_note',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'replaced_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
