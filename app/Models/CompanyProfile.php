<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CompanyProfile extends Model
{
    use HasUuids;

    protected $table = 'company_profiles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'logo',
        'address',
        'handphone',
        'email',
        'status',
    ];

    /**
     * Relasi ke user (pemilik / admin yang membuat data company profile)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke branch-branch milik company profile ini
     */
    public function branches()
    {
        return $this->hasMany(CompanyBranch::class, 'company_profile_id');
    }
}
