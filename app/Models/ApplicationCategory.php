<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApplicationCategory extends Model
{
    use HasFactory;

    protected $table = 'application_categories';

    // karena pakai UUID
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'slug',
        'status',
    ];

    /**
     * Auto generate UUID saat create
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            // optional: auto generate slug kalau belum diisi
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Scope aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}