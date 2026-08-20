<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleUser extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'role_user';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'role_id',
        'status',
        'company_branch_id',
        'company_division_id',
    ];

    /**
     * User yang memiliki role.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    /**
     * Role yang dimiliki user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'role_id',
            'id'
        );
    }

    /**
     * Company branch scope assignment ini (diisi kalau role.scope_level = 'branch').
     */
    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'company_branch_id', 'id');
    }

    /**
     * Company division scope assignment ini (diisi kalau role.scope_level = 'division').
     */
    public function companyDivision(): BelongsTo
    {
        return $this->belongsTo(CompanyDivision::class, 'company_division_id', 'id');
    }

    /**
     * Scope role aktif.
     */
    public function scopeActive($query)
    {
        return $query->where(
            'status',
            self::STATUS_ACTIVE
        );
    }
}