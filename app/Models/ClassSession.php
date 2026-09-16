<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 1 pengajuan pemakaian credit untuk 1 kelas -- lihat docblock migration
 * create_class_sessions_table untuk alur status lengkapnya, dan
 * App\Services\ClassSession\ClassSessionWorkflowService untuk logika
 * transisi antar statusnya (JANGAN ubah kolom `status` langsung di luar
 * service itu, supaya aturan/pengecekan di tiap transisi selalu dilewati).
 */
class ClassSession extends Model
{
    use HasUuids;

    protected $table = 'class_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_WAITING_TEACHER = 'menunggu_guru';

    public const STATUS_REJECTED_BY_TEACHER = 'ditolak_guru';

    public const STATUS_WAITING_ADMIN = 'menunggu_admin';

    public const STATUS_APPROVED = 'disetujui';

    public const STATUS_REJECTED_BY_ADMIN = 'ditolak_admin';

    protected $fillable = [
        'student_id',
        'teacher_user_id',
        'course_package_id',
        'branch_id',
        'credit_amount_requested',
        'credit_amount_final',
        'status',
        'notes',
        'course_credit_id',
        'requested_at',
        'teacher_approved_at',
        'admin_approved_at',
        'admin_user_id',
    ];

    protected $casts = [
        'credit_amount_requested' => 'decimal:2',
        'credit_amount_final' => 'decimal:2',
        'requested_at' => 'datetime',
        'teacher_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function courseCredit(): BelongsTo
    {
        return $this->belongsTo(CourseCredit::class, 'course_credit_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Honor pengajar hasil sesi ini -- lihat App\Models\TeacherHonor &
     * App\Services\TeacherHonor\TeacherHonorService (Fase 3). Cuma terisi
     * SETELAH status jadi 'disetujui'.
     */
    public function teacherHonor(): HasOne
    {
        return $this->hasOne(TeacherHonor::class, 'class_session_id');
    }
}
