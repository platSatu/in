<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
}
