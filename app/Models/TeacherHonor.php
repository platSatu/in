<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris honor pengajar dari 1 ClassSession -- lihat docblock migration
 * create_teacher_honors_table & App\Services\TeacherHonor\TeacherHonorService
 * untuk alur lengkapnya. JANGAN ubah kolom `status` langsung di luar
 * service itu.
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
        'class_session_id',
        'teacher_user_id',
        'student_id',
        'branch_id',
        'commission_percentage',
        'credit_value',
        'honor_amount',
        'status',
        'approved_by_user_id',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'credit_value' => 'decimal:2',
        'honor_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
