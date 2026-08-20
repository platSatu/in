<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FormQuestion extends Model
{
    use HasUuids;

    protected $table = 'form_questions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'form_id',
        'parent_option_id',
        'stage_group',
        'question_text',
        'description',
        'image',
        'audio',
        'type',
        'required',
        'order',
        'status',
    ];

    /**
     * Relasi ke Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

/**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Options
     */
    public function options()
    {
        return $this->hasMany(FormQuestionOption::class, 'question_id')
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Opsi (milik pertanyaan LAIN) yang jadi "pemicu" pertanyaan ini — kalau
     * kosong, pertanyaan ini adalah pertanyaan utama (root), lihat migration
     * add_parent_option_id_to_form_questions_table.
     */
    public function parentOption()
    {
        return $this->belongsTo(FormQuestionOption::class, 'parent_option_id');
    }
}
