<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfile extends Model
{
    use HasUuids;

    protected $table = 'university_profiles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'university_id',
        'field',
        'min_budget',
        'max_budget',
        'language',
        'scholarship_available',
        'status',
        'degree',
        'intake',
    ];

    /**
     * Relasi ke University
     */
    public function university()
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    /**
     * Relasi ke User (creator profile)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}