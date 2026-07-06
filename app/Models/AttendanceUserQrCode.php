<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceUserQrCode extends Model
{
    use HasUuids;

    protected $table = 'attendance_user_qr_codes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'qr_token',
        'qr_code_path',
        'status',
        'last_rotated_at',
        'expires_at',
    ];

    /**
     * Relasi ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}