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
        'section_id',
        'question_text',
        'description',
        'image',
        'audio',
        'type',
        'correct_answer',
        'match_score',
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

    /**
     * Section pembungkus pertanyaan ini (mis. "Section A") — opsional/nullable,
     * lihat migration add_section_id_to_form_questions_table & FormSection.
     * NULL berarti pertanyaan ini belum dikelompokkan ke section mana pun.
     */
    public function section()
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }
}
