<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class University extends Model
{
    use HasUuids;

    protected $table = 'universities';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'country',
        'city',
        'description',
    ];

    /**
     * Relasi ke user (pemilik / admin yang membuat data universitas)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}