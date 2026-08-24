<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSection extends Model
{
    use HasUuids;

    protected $table = 'form_sections';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'form_id',
        'name',
        'description',
        'order',
        'status',
    ];

    /**
     * Relasi ke Form pemilik section ini.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Seluruh pertanyaan (root maupun anak/bercabang) yang ditempatkan di
     * section ini. Diurutkan sama seperti FormQuestion::options() &
     * FrontendController::buildFormWizardView() supaya konsisten di mana pun
     * dipakai (halaman admin maupun wizard publik).
     */
    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class, 'section_id')
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at');
    }
}
