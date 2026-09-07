<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cermin lokal 1 meeting Zoom (menu Zoom > Kelola Meeting) -- dibuat/diubah/
 * diakhiri lewat App\Services\Zoom\ZoomClient, tabel ini menyimpan hasil &
 * status terakhirnya. Lihat migration create_zoom_meetings_table.
 */
class ZoomMeeting extends Model
{
    use HasUuids;

    protected $table = 'zoom_meetings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'zoom_meeting_id',
        'zoom_uuid',
        'topic',
        'agenda',
        'type',
        'start_time',
        'duration',
        'timezone',
        'password',
        'join_url',
        'start_url',
        'settings',
        'status',
        'ended_at',
        'recordings_synced_at',
    ];

    protected $casts = [
        'type' => 'integer',
        'start_time' => 'datetime',
        'duration' => 'integer',
        'settings' => 'array',
        'ended_at' => 'datetime',
        'recordings_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(ZoomMeetingRecording::class, 'zoom_meeting_id');
    }

    /**
     * Opsi default meeting (dipakai form create kalau admin belum mengubah
     * apa pun) -- join_before_host dimatikan & waiting_room dinyalakan
     * sebagai default paling aman.
     *
     * @return array<string, bool>
     */
    public static function defaultSettings(): array
    {
        return [
            'join_before_host' => false,
            'waiting_room' => true,
            'host_video' => true,
            'participant_video' => false,
            'mute_upon_entry' => true,
        ];
    }
}
