<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan notifikasi dokumen yang dikirim (lihat App\Services\InaStudy\DocumentNotifier). */
class DocumentNotificationLog extends Model
{
    use HasUuids;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $table = 'document_notification_logs';

    protected $fillable = ['application_id', 'type', 'channel', 'recipient', 'status', 'error'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }
}
