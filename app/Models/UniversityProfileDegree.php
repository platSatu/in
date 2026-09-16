<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfileDegree extends Model
{
    use HasUuids;

    protected $table = 'university_profile_degrees';

    protected $keyType = 'string';

    public $incrementing = false;

    // FIX (15 September 2026, permintaan user): 4 pilihan Degree yang boleh
    // dipilih di dropdown form (bukan enum DB, lihat catatan di migration
    // add_course_fields_to_university_profile_degrees_table) -- dipakai
    // bareng oleh view create/edit & validasi di UniversityProfileController.
    public const DEGREES = ['Diploma', 'Bachelor', 'Master', 'PhD'];

    // FIX (permintaan user, 16 September 2026): 4 pilihan tetap untuk field
    // "CSCA Subject" per Course (bukan enum DB, sama polanya dengan DEGREES
    // di atas) -- dipakai bareng oleh view create/edit & validasi di
    // UniversityProfileController.
    public const CSCA_SUBJECTS = ['CSCA Math', 'CSCA Physics', 'CSCA Humanities', 'CSCA Chemistry'];

    protected $fillable = [
        'user_id',
        'university_profile_id',
        'degree',
        // FIX (15 September 2026, permintaan user -- flow "pilih kampus ->
        // degree -> jurusan"): 1 baris ini sekarang mewakili 1 "Course" utuh
        // di bawah Degree terpilih, bukan cuma degree+intake+duration lagi.
        'course_name',
        'intake',
        'duration',
        'starting_date',
        'application_deadline',
        'language',
        'tuition_fee',
        // FIX (permintaan user, 16 September 2026): Registration Fee &
        // kategori CSCA Subject sekarang ditentukan DI SINI (per Course),
        // bukan lagi diisi manual admin per-aplikasi SETELAH siswa submit --
        // lihat migration add_registration_fee_and_csca_subject_to_
        // university_profile_degrees_table & ApplyController::store().
        'registration_fee_amount',
        'csca_subject',
        'sort_order',
    ];

    protected $casts = [
        'starting_date' => 'date',
        'application_deadline' => 'date',
        'tuition_fee' => 'integer',
        'registration_fee_amount' => 'integer',
    ];

    /**
     * Relasi ke UniversityProfile induknya.
     */
    public function profile()
    {
        return $this->belongsTo(UniversityProfile::class, 'university_profile_id');
    }

    /**
     * Relasi ke User (creator baris ini).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
