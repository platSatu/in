<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Country extends Model
{
    use HasUuids;

    protected $table = 'countries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke User (pemilik / admin yang membuat data country)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke City (root hierarki University: Country -> City -> Major -> University)
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'country_id');
    }
}
