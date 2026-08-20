<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyDivision extends Model
{
    use HasUuids;

    protected $table = 'company_division';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'company_branch_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke user (pemilik / admin yang membuat data divisi)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke company branch induk
     */
    public function companyBranch()
    {
        return $this->belongsTo(CompanyBranch::class, 'company_branch_id');
    }

    /**
     * Baris pivot company_division_user milik divisi ini.
     */
    public function divisionUsers(): HasMany
    {
        return $this->hasMany(CompanyDivisionUser::class, 'company_division_id');
    }

    /**
     * User-user yang tergabung dalam divisi ini.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'company_division_user',
            'company_division_id',
            'user_id'
        )->withPivot('id', 'status')->withTimestamps();
    }
}
