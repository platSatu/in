<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers';

    // UUID settings
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'application_category_id',
        'code_vouchers',
        'status',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Boot UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            // optional: auto uppercase voucher code
            if (!empty($model->code_vouchers)) {
                $model->code_vouchers = strtoupper($model->code_vouchers);
            }
        });
    }

    /**
     * Scope active voucher
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check apakah voucher masih valid berdasarkan tanggal
     */
    public function isValid()
    {
        $today = Carbon::today();

        return $this->status === 'active'
            && $today->between($this->valid_from, $this->valid_until);
    }

    /**
     * Relationship ke application category
     */
    public function category()
    {
        return $this->belongsTo(ApplicationCategory::class, 'application_category_id');
    }
}