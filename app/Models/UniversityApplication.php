<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu Aplikasi Kuliah: satu Student apply ke satu Major (UniversityProfile)
 * di satu kampus. degree/intake/duration/registration_fee_amount adalah
 * SNAPSHOT saat submit (lihat catatan di migration-nya) -- jangan diasumsikan
 * selalu sama dengan data terbaru di UniversityProfileDegree/Payment.
 */
class UniversityApplication extends Model
{
    use HasUuids;

    protected $table = 'university_applications';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_DOCUMENTS_REVIEW = 'documents_review';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_VISA_PROCESS = 'visa_process';
    public const STATUS_CHECK_IN = 'check_in';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'application_no',
        'student_id',
        'university_profile_id',
        'university_id',
        'degree',
        'intake',
        'duration',
        'whatsapp',
        'registration_fee_amount',
        'registration_fee_paid_at',
        'deposit_fee_china_amount',
        'status',
        'handled_by_user_id',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'registration_fee_amount' => 'integer',
        'registration_fee_paid_at' => 'date',
        'deposit_fee_china_amount' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function universityProfile(): BelongsTo
    {
        return $this->belongsTo(UniversityProfile::class, 'university_profile_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function checklistEntries(): HasMany
    {
        return $this->hasMany(ApplicationChecklistEntry::class, 'application_id');
    }
}
