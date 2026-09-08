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
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'replaced_at' => 'datetime',
    ];

    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
