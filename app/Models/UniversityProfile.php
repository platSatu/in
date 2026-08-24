<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UniversityProfile extends Model
{
    use HasUuids;

    protected $table = 'university_profiles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'university_id',
        'field',
        'min_budget',
        'max_budget',
        'language',
        'scholarship_available',
        'status',
    ];

    /**
     * Relasi ke University
     */
    public function university()
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    /**
     * Relasi ke User (creator profile)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Daftar Degree + Intake (bisa lebih dari satu per profile, diisi lewat
     * fitur "add row" di halaman create). Tabel `university_profiles` sendiri
     * TIDAK punya kolom `degree`/`intake` (sempat dikira ada, ternyata tidak
     * — lihat riwayat error 1054 "Unknown column 'degree'"), jadi seluruh
     * data degree/intake memang cuma hidup di tabel anak ini.
     */
    public function degrees()
    {
        return $this->hasMany(UniversityProfileDegree::class, 'university_profile_id')
            ->orderBy('sort_order');
    }
}
