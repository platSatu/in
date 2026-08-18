<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_templates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'description',
        'status',
    ];

    /**
     * Admin yang membuat/memiliki template ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Form-form yang memakai template ini.
     * Aktifkan setelah kolom whatsapp_template_id ditambahkan ke tabel forms.
     */
    public function forms()
    {
        return $this->hasMany(Form::class, 'whatsapp_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}