<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceSetting extends Model
{
    use HasUuids;

    protected $table = 'attendance_settings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'check_in_time',
        'check_out_time',
        'status',
    ];
}