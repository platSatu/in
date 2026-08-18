<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invitation extends Model
{
    use HasUuids;

    protected $table = 'invitations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'handphone',
        'university',
        'program',
        'number_of_attendes',
        'status',
        'qrcode',
        'checked_in_at',
        'directory_qrcode',
    ];

    protected $casts = [
        'number_of_attendes' => 'integer',
        'checked_in_at' => 'datetime',
    ];
}