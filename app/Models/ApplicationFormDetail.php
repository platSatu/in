<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FASE 3 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- biodata
 * lengkap "Formulir" yang diisi siswa LANGSUNG di web (Step 1, setelah
 * Registration Fee lunas), menggantikan upload file Word/PDF manual untuk
 * DocumentType 'formulir' (lihat DocumentTypeSeeder, yang menonaktifkan
 * DocumentType itu begitu fitur ini aktif).
 *
 * 1 baris di sini = 1 UniversityApplication (relasi 1-ke-1). Lihat
 * ApplicationEducationBackground untuk bagian "Education Background"
 * (1-ke-banyak, tabel terpisah).
 */
class ApplicationFormDetail extends Model
{
    use HasUuids;

    protected $table = 'application_form_details';

    protected $keyType = 'string';

    public $incrementing = false;

    public const SPONSORSHIP_SCHOLARSHIP = 'scholarship';
    public const SPONSORSHIP_SELF_SPONSORED = 'self_sponsored';

    protected $fillable = [
        'application_id',
        'surname',
        'given_name',
        'chinese_name',
        'photo_path',
        'gender',
        'nationality',
        'passport_no',
        'passport_expiry_date',
        'telephone_no',
        'date_of_birth',
        'place_of_birth',
        'hobby',
        'parents_name',
        'parents_phone',
        'parents_occupation',
        'home_address',
        'email',
        'religion',
        'highest_degree_obtained',
        'field_of_study_in_china',
        'financial_support_by',
        'sponsorship_type',
        'terms_accepted_at',
    ];

    protected $casts = [
        'passport_expiry_date' => 'date',
        'date_of_birth' => 'date',
        'terms_accepted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UniversityApplication::class, 'application_id');
    }

    public function isSubmitted(): bool
    {
        return !empty($this->terms_accepted_at);
    }
}
