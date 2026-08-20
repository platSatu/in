<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class University extends Model
{
    use HasUuids;

    protected $table = 'universities';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'major_id',
        'name',
        'country',
        'city',
        'description',
        'status',
        'logo',
        'banner',
        'attachment',
    ];

    /**
     * Relasi ke user (pemilik / admin yang membuat data universitas)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city');
    }

    /**
     * Relasi ke Major (opsional — university bisa ditambahkan langsung dari
     * index Major, tapi field ini tidak wajib supaya university lama/berdiri
     * sendiri tetap valid).
     */
    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    /**
     * Relasi ke UniversityAlbum, dipakai di halaman detail (show) untuk
     * menampilkan seluruh foto kampus (album -> photos).
     */
    public function albums()
    {
        return $this->hasMany(UniversityAlbum::class, 'university_id');
    }

    /**
     * Relasi ke UniversityProfile, dipakai di halaman detail (show) untuk
     * menampilkan field/budget/bahasa/beasiswa yang sudah diisi.
     */
    public function profiles()
    {
        return $this->hasMany(UniversityProfile::class, 'university_id');
    }
}
