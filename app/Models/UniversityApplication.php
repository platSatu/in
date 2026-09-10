<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu Aplikasi Kuliah: satu Student apply ke satu Major (UniversityProfile)
 * di satu kampus. degree/language/intake/intake_year/duration/
 * registration_fee_amount adalah SNAPSHOT saat submit (lihat catatan di
 * migration-nya) -- jangan diasumsikan selalu sama dengan data terbaru di
 * UniversityProfileDegree/Payment/UniversityProfile.
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

    // FIX (10 September 2026): fitur "Alur Pembayaran 2 Arah Apply Kampus" --
    // status UNDER REVIEW/PROCESSING/ACCEPTED, SENGAJA terpisah dari
    // konstanta STATUS_* di atas supaya stepper/filter yang sudah jalan
    // berdasarkan 'status' tidak ikut berubah. 'under_review' otomatis
    // diset begitu Registration Fee sukses dibayar (Step 1 & 2 terbuka);
    // 'processing' & 'accepted' diubah manual oleh admin.
    public const ADMISSION_STATUS_UNDER_REVIEW = 'under_review';
    public const ADMISSION_STATUS_PROCESSING = 'processing';
    public const ADMISSION_STATUS_ACCEPTED = 'accepted';

    protected $fillable = [
        'application_no',
        'student_id',
        'university_profile_id',
        'university_id',
        'degree',
        'language',
        'intake',
        'intake_year',
        'duration',
        'whatsapp',
        'registration_fee_amount',
        'registration_fee_paid_at',
        'deposit_fee_china_amount',
        'status',
        // FIX (10 September 2026): lihat catatan ADMISSION_STATUS_* di atas.
        'admission_status',
        'handled_by_user_id',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'intake_year' => 'integer',
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

    // FIX (10 September 2026): fitur "Alur Pembayaran 2 Arah Apply Kampus".
    public function payments(): HasMany
    {
        return $this->hasMany(ApplicationPayment::class, 'application_id');
    }

    // FASE 3 (10 September 2026): Step 1 "Formulir" web (biodata lengkap),
    // relasi 1-ke-1 -- lihat ApplicationFormDetail.
    public function formDetail(): HasOne
    {
        return $this->hasOne(ApplicationFormDetail::class, 'application_id');
    }

    // FASE 3 -- baris "Education Background" (fitur "add row"), 1-ke-banyak,
    // lihat ApplicationEducationBackground.
    public function educationBackgrounds(): HasMany
    {
        return $this->hasMany(ApplicationEducationBackground::class, 'application_id')->orderBy('sort_order');
    }
}
