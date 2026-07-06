<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    // UUID settings
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'application_category_id',
        'name',
        'description',
        'image',
        'status',
        'price',
        'duration_days',
    ];

    /**
     * Boot method untuk UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope aktif package
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function applicationCategory(): BelongsTo
    {
        return $this->belongsTo(ApplicationCategory::class, 'application_category_id');
    }

    /**
     * Format harga (helper optional)
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.');
    }
}
