<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserPreference extends Model
{
    use HasUuids;

    protected $table = 'user_preferences';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'field_of_study',
        'min_budget',
        'max_budget',
        'preferred_language',
        'scholarship_needed',
        'country',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'min_budget' => 'integer',
            'max_budget' => 'integer',
            'scholarship_needed' => 'boolean',
        ];
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
