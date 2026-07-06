<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProfileBussines extends Model
{
    use HasUuids;

    protected $table = 'profile_bussines';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'parent_id' => 'string',
    ];

    /**
     * Relasi ke user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Parent business.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /**
     * Child businesses.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }
}