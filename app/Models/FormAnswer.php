<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FormAnswer extends Model
{
    use HasUuids;

    protected $table = 'form_answers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'submission_id',
        'question_id',
        'option_id',
        'answer_text',
        'status',
    ];

    /**
     * Relasi ke submission
     */
    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    /**
     * Relasi ke question
     */
    public function question()
    {
        return $this->belongsTo(FormQuestion::class, 'question_id');
    }

    /**
     * Relasi ke option (kalau pilihan ganda)
     */
    public function option()
    {
        return $this->belongsTo(FormQuestionOption::class, 'option_id');
    }

    /**
     * Relasi ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}