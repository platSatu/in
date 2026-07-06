<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HistoryUserLogin extends Model
{
    protected $table = 'history_user_login';

    protected $fillable = [
        'id',
        'user_id',
        'last_login',
        'last_logout',
        'duration',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'last_logout' => 'datetime',
    ];

    /**
     * Auto generate UUID saat create
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relasi ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}