<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityAlbumPhoto extends Model
{
    use HasUuids;

    protected $table = 'university_album_photos';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'album_id',
        'photo',
        'title',
        'description',
        'sort_order',
        'status',
    ];

    /**
     * Relasi ke Album
     */
    public function album()
    {
        return $this->belongsTo(
            UniversityAlbum::class,
            'album_id'
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
}