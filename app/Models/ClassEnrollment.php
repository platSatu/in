<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu student terdaftar di satu ClassSchedule, dibuat dari
 * halaman publik "Pilih Kelas" (ClassSelectionController::store()) setelah
 * hasil placement test-nya keluar. `form_submission_id` UNIQUE di level
 * database (lihat migration create_class_enrollments_table) — satu
 * submission cuma bisa punya 1 pendaftaran kelas.
 */
class ClassEnrollment extends Model
{
    use HasUuids;

    protected $table = 'class_enrollments';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'class_schedule_id',
        'student_id',
        'form_submission_id',
        'status',
    ];

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
