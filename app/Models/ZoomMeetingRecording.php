<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu file recording (video/audio/transkrip/chat) milik satu
 * ZoomMeeting, hasil sinkronisasi dari Zoom API. Lihat migration
 * create_zoom_meeting_recordings_table.
 */
class ZoomMeetingRecording extends Model
{
    use HasUuids;

    protected $table = 'zoom_meeting_recordings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'zoom_meeting_id',
        'recording_file_id',
        'recording_type',
        'file_type',
        'file_size',
        'play_url',
        'download_url',
        'recording_start',
        'recording_end',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'recording_start' => 'datetime',
        'recording_end' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ZoomMeeting::class, 'zoom_meeting_id');
    }
}
