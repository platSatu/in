<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CompanyBranch extends Model
{
    use HasUuids;

    protected $table = 'company_branch';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'company_profile_id',
        'name',
        'description',
        'logo',
        'address',
        'handphone',
        'email',
        'status',
    ];

    /**
     * Relasi ke user (pemilik / admin yang membuat data branch)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke company profile induk
     */
    public function companyProfile()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    /**
     * Relasi ke divisi-divisi milik branch ini
     */
    public function divisions()
    {
        return $this->hasMany(CompanyDivision::class, 'company_branch_id');
    }

    /**
     * Relasi ke form/booth yang didaftarkan pada branch ini
     */
    public function forms()
    {
        return $this->hasMany(Form::class, 'branch_id');
    }

    /**
     * Relasi ke jadwal kelas kursus milik branch ini (menu Quiz > Class
     * Schedule) — dipakai peserta setelah hasil placement test keluar untuk
     * memilih kelas, lihat App\Models\ClassSchedule.
     */
    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'branch_id');
    }
}
