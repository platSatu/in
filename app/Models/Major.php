<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Major extends Model
{
    use HasUuids;

    protected $table = 'majors';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'city_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke City.
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * University yang terhubung lewat menu Setting University (dipakai {{universitas_major}} di WA).
     */
    public function settingUniversities()
    {
        return $this->hasMany(SettingUniversity::class, 'major_id');
    }
}
