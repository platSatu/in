<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FormQuestionOption extends Model
{
    use HasUuids;

    protected $table = 'form_question_options';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'question_id',
        'order',
        'option_text',
        'image',
        'score',
        'is_other',
        'status',
    ];

    protected $casts = [
        'is_other' => 'boolean',
    ];

    /**
     * Relasi ke pertanyaan
     */
    public function question()
    {
        return $this->belongsTo(FormQuestion::class, 'question_id');
    }

    /**
     * Relasi ke user pembuat
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}