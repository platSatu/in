<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormResult extends Model
{
    use HasUuids;

    protected $table = 'form_results';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'form_submission_id',
        'form_id',
        'mode',
        'score',
        'summary_text',
        'entered_by',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'whatsapp_sent_at' => 'datetime',
    ];

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Admin yang input hasil ini secara manual (null kalau mode='auto').
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
