<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Honor 1 pengajar dalam 1 periode -- dibuat saat periode ditutup, dengan
 * rekap per kelas yang dikunci di kolom `classes` (fee tidak ikut berubah
 * kalau Course Class diedit belakangan). JANGAN ubah kolom `status`
 * langsung di luar App\Services\TeacherHonor\TeacherHonorService.
 */
class TeacherHonor extends Model
{
    use HasUuids;

    protected $table = 'teacher_honors';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED_FOR_PAYOUT = 'approved_for_payout';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'teacher_honor_period_id',
        'teacher_user_id',
        'class_count',
        'honor_amount',
        'classes',
        'status',
        'approved_by_user_id',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'class_count' => 'integer',
        'honor_amount' => 'decimal:2',
        'classes' => 'array',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(TeacherHonorPeriod::class, 'teacher_honor_period_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
