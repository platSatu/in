<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityAlbum extends Model
{
    use HasUuids;

    protected $table = 'university_albums';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'university_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke University
     */
    public function university()
    {
        return $this->belongsTo(
            University::class,
            'university_id'
        );
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Relasi ke Album Photos
     */
    public function photos()
    {
        return $this->hasMany(
            UniversityAlbumPhoto::class,
            'album_id'
        );
    }
}