<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status satu item checklist (ApplicationChecklistItem) UNTUK satu
 * Aplikasi tertentu -- selesai/belum, catatan, foto bukti, kapan & siapa
 * yang menandai. Dikelola superadmin; sumber data stepper read-only di
 * dashboard siswa.
 */
class ApplicationChecklistEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'application_id',
        'checklist_item_id',
        'is_done',
        'note',
        'photo_path',
        'done_at',
        'done_by_user_id',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ApplicationChecklistItem::class, 'checklist_item_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by_user_id');
    }
}
