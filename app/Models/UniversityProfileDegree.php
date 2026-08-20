<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfileDegree extends Model
{
    use HasUuids;

    protected $table = 'university_profile_degrees';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'university_profile_id',
        'degree',
        'intake',
        'sort_order',
    ];

    /**
     * Relasi ke UniversityProfile induknya.
     */
    public function profile()
    {
        return $this->belongsTo(UniversityProfile::class, 'university_profile_id');
    }

    /**
     * Relasi ke User (creator baris ini).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
