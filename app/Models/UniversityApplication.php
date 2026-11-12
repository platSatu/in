<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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
    // Upgrade kampus/jurusan (10 November 2026): diset otomatis saat admin memindahkan aplikasi yang sudah bayar
    // ke jurusan dengan Registration Fee lebih mahal; kembali 'under_review' begitu kekurangannya lunas.
    public const ADMISSION_STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    protected $fillable = [
        'application_no',
        'student_id',
        'university_profile_id',
        // FIX (permintaan user, 16 September 2026): snapshot Course
        // (jurusan) yang dipilih siswa saat Apply -- lihat catatan lengkap
        // di migration add_course_snapshot_to_university_applications_table.
        // Sebelum ini, degree_intake_id cuma dipakai buat VALIDASI saat
        // submit (exists:university_profile_degrees,id) lalu dibuang --
        // tidak pernah ikut disimpan, jadi kalau 1 Program punya lebih dari
        // 1 Course dengan Degree/Intake/Duration yang sama, admin tidak
        // bisa tahu jurusan mana persisnya yang dipilih siswa.
        'degree_intake_id',
        'course_name',
        'university_id',
        'degree',
        'language',
        'intake',
        'intake_year',
        'duration',
        'whatsapp',
        'registration_fee_amount',
        // Kekurangan Registration Fee setelah upgrade jurusan (lihat ApplicationAdminService::changeCourse()).
        'registration_fee_due',
        'registration_fee_paid_at',
        'deposit_fee_china_amount',
        'status',
        'cancel_reason',
        // FIX (10 September 2026): lihat catatan ADMISSION_STATUS_* di atas.
        'admission_status',
        'handled_by_user_id',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'intake_year' => 'integer',
        'registration_fee_amount' => 'integer',
        'registration_fee_due' => 'integer',
        'registration_fee_paid_at' => 'date',
        'deposit_fee_china_amount' => 'integer',
        'submitted_at' => 'datetime',
    ];

    // Tahap lanjut: kampus/jurusan tidak bisa diubah lagi (batalkan lalu apply baru).
    public const LOCKED_STATUSES = [
        self::STATUS_REGISTERED,
        self::STATUS_VISA_PROCESS,
        self::STATUS_CHECK_IN,
        self::STATUS_COMPLETED,
    ];

    /**
     * Snapshot kampus & jurusan dari 1 Course -- satu sumber untuk Apply,
     * Register manual & Ubah Kampus/Jurusan oleh admin (22 Oktober 2026).
     * registration_fee_amount sengaja tidak di sini (aturannya beda tiap pintu).
     */
    public static function courseSnapshot(UniversityProfileDegree $course): array
    {
        $profile = $course->profile;

        return [
            'university_profile_id' => $profile->id,
            'university_id' => $profile->university_id,
            'degree_intake_id' => $course->id,
            'course_name' => $course->course_name,
            'degree' => $course->degree,
            'language' => $profile->language,
            'intake' => $course->intake,
            'duration' => $course->duration,
        ];
    }

    /**
     * Nominal Registration Fee yang ditagih sekarang: kekurangan/pelunasan yang ditagih admin, kalau ada.
     * Apply lebih dari 1 kampus (12 November 2026): tiap kampus cukup DP 50%, sisanya ditagih admin belakangan.
     */
    public function registrationFeeToCharge(): int
    {
        if ($this->registration_fee_due) {
            return (int) $this->registration_fee_due;
        }

        $multiCampus = static::where('student_id', $this->student_id)->where('status', '!=', self::STATUS_CANCELLED)->count() > 1;

        return $multiCampus ? (int) ceil($this->registration_fee_amount / 2) : (int) $this->registration_fee_amount;
    }

    /** Sisa Registration Fee yang belum dibayar (dasar tagihan pelunasan dari admin). */
    public function registrationFeeRemaining(): int
    {
        $paid = $this->payments()->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE)
            ->where('status', ApplicationPayment::STATUS_PAID)->sum('amount');

        return max(0, (int) $this->registration_fee_amount - (int) $paid);
    }

    /**
     * Aplikasi yang Registration Fee-nya dibayar sekaligus dalam satu transaksi (cart "Pilihan Kampus"):
     * semua aplikasi aktif milik student yang sama dan belum dibayar sama sekali. Tagihan pelunasan selalu sendiri.
     */
    public function registrationBundle(): Collection
    {
        if ($this->registration_fee_due) {
            return collect([$this]);
        }

        return static::with('university')
            ->where('student_id', $this->student_id)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->whereNull('registration_fee_due')
            ->whereDoesntHave('payments', fn ($query) => $query->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE)
                ->where('status', ApplicationPayment::STATUS_PAID))
            ->orderBy('submitted_at')
            ->get();
    }

    /** Menunggu kekurangan setelah upgrade jurusan (Formulir & Dokumen terkunci), beda dengan tagihan pelunasan DP. */
    public function isAwaitingUpgradePayment(): bool
    {
        return $this->admission_status === self::ADMISSION_STATUS_AWAITING_PAYMENT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

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

    /**
     * Label kolom "Major" di tabel/detail aplikasi (7 Oktober 2026): jurusan
     * (Course) yang dipilih saat Apply/Register, sama seperti label di form
     * Apply -- mis. "Corporate Management (Bachelor)". Bukan dari
     * universityProfile->field (sering kosong & tidak dipakai di alur Apply).
     */
    public function getMajorLabelAttribute(): string
    {
        // Aplikasi lama (sebelum course_name ikut di-snapshot) jatuh ke nama Program.
        if (blank($this->course_name)) {
            return optional($this->universityProfile)->field ?: '-';
        }

        return $this->course_name.(filled($this->degree) ? ' ('.$this->degree.')' : '');
    }

    /** Cache per request: jenis dokumen aktif & aturan per kampus (dipakai berulang di daftar aplikasi). */
    private static ?Collection $studentDocumentTypes = null;
    private static array $campusRules = [];

    /**
     * Jenis dokumen yang diupload student untuk aplikasi ini (12 November 2026): mengikuti Dokumen
     * Persyaratan kampusnya (disaring jenjang & bahasa pengantar), ditambah jenis yang sudah telanjur
     * diupload supaya file lama tidak pernah hilang. Kampus yang belum diatur: semua jenis aktif.
     * is_required, campus_note & template_file_path ikut aturan kampus (hanya di memori, tidak disimpan).
     */
    public function documentTypes(): Collection
    {
        $types = (self::$studentDocumentTypes ??= DocumentType::active()->studentUpload()->orderBy('sort_order')->get())
            ->map(fn (DocumentType $type) => clone $type);
        $rules = self::$campusRules[$this->university_id] ??= UniversityDocumentRequirement::where('university_id', $this->university_id)
            ->get()->keyBy('document_type_id');

        if ($rules->isEmpty()) {
            return $types;
        }

        $uploaded = $this->documents()->distinct()->pluck('document_type_id');

        return $types->filter(function (DocumentType $type) use ($rules, $uploaded) {
            $rule = $rules->get($type->id);

            if ($rule && $rule->appliesTo($this)) {
                $type->is_required = $rule->is_required;
                $type->campus_note = $rule->note;
                $type->template_file_path = $rule->template_file_path ?: $type->template_file_path;

                return true;
            }

            $type->is_required = false;

            return $uploaded->contains($type->id);
        })->values();
    }

    /**
     * Hitungan dokumen untuk progress (13 Oktober 2026), dipisah supaya
     * dokumen dari INA (Offer Letter, JW, VISA) tidak membuat dokumen
     * student terlihat belum lengkap:
     *  - documents_count: dokumen STUDENT (jenis aktif, provided_by student)
     *  - ina_documents_count: dokumen dari INA (jenis aktif, provided_by admin)
     * Pasangannya documentTypes()->count() (per kampus) / DocumentType::adminProvidedTotal().
     */
    public function scopeWithDocumentProgress($query)
    {
        return $query->withCount([
            // count distinct: jenis yang boleh banyak file (Any Certificate) tetap dihitung 1 jenis.
            'documents' => fn ($q) => $q->whereHas('documentType', fn ($t) => $t->active()->studentUpload())
                ->select(\Illuminate\Support\Facades\DB::raw('count(distinct document_type_id)')),
            'documents as ina_documents_count' => fn ($q) => $q->whereHas('documentType', fn ($t) => $t->active()->adminProvided()),
        ]);
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
