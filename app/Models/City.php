<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class City extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'country_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke Country (root hierarki University)
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relasi ke University. FK eksplisit 'city' karena kolom di tabel
     * `universities` bernama `city` (bukan `city_id`) — sebelumnya relasi ini
     * memakai default guess Laravel (city_id) yang salah dan tidak pernah
     * mengembalikan data.
     */
    public function universities()
    {
        return $this->hasMany(University::class, 'city');
    }
}
