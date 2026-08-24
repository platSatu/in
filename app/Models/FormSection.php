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
        'parent_section_id',
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
     * Section "induk" dari Sub Section ini — null berarti section ini
     * TOP-LEVEL (baik section 1-level biasa, maupun Section induk di
     * hierarki 2 level HSK-style). Lihat migration
     * add_parent_section_id_to_form_sections_table.
     */
    public function parentSection(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'parent_section_id');
    }

    /**
     * Seluruh Sub Section aktif milik Section (top-level) ini. Cuma
     * bermakna kalau section ini sendiri top-level — hierarki sengaja
     * dibatasi 2 level saja (lihat FormSectionController).
     */
    public function subSections(): HasMany
    {
        return $this->hasMany(FormSection::class, 'parent_section_id')
            ->where('status', 'active')
            ->orderBy('order')
            ->orderBy('created_at');
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
