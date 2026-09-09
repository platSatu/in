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
        'is_correct',
        'status',
        'whatsapp_template_id',
    ];

    protected $casts = [
        'is_other' => 'boolean',
        'is_correct' => 'boolean',
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

    /**
     * Pertanyaan bercabang (conditional/nested question): daftar pertanyaan
     * "anak" yang hanya ditampilkan ke peserta kalau opsi ini yang dipilih.
     * Bisa berlapis tak terbatas — opsi milik pertanyaan anak di sini juga
     * bisa punya childQuestions()-nya sendiri.
     */
    public function childQuestions()
    {
        return $this->hasMany(FormQuestion::class, 'parent_option_id')
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Template WhatsApp khusus opsi ini (fitur tambahan, terpisah dari
     * whatsapp_template_id milik Form). Kalau peserta memilih opsi ini dan
     * kolom ini diisi, satu pesan WA terpisah dikirim memakai template ini
     * -- di luar (bukan pengganti) pesan per-form yang sudah ada. Lihat
     * FrontendController@sendPerOptionWhatsappMessages.
     */
    public function whatsappTemplate()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}