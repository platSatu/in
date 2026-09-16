<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfileScholarship extends Model
{
    use HasUuids;

    protected $table = 'university_profile_scholarships';

    protected $keyType = 'string';

    public $incrementing = false;

    // Nilai currency yang boleh dipilih di dropdown form (bukan enum DB) --
    // dipakai bareng oleh view create/edit & validasi di
    // UniversityProfileController, sama polanya dengan
    // UniversityProfileDegree::DEGREES.
    public const CURRENCIES = ['rupiah', 'yuan'];

    protected $fillable = [
        'user_id',
        'university_profile_id',
        'name',
        'price',
        'currency',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
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
