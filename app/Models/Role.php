<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * scope_level: seberapa luas data yang boleh dilihat pemegang role ini.
     * Lihat docblock App\Concerns\HasScopedAccess untuk penjelasan tiap nilai.
     */
    public const SCOPE_COMPANY = 'company';
    public const SCOPE_BRANCH = 'branch';
    public const SCOPE_DIVISION = 'division';
    public const SCOPE_SELF = 'self';

    public const SCOPE_LEVELS = [
        self::SCOPE_COMPANY,
        self::SCOPE_BRANCH,
        self::SCOPE_DIVISION,
        self::SCOPE_SELF,
    ];

    /**
     * Urutan "keluasan" tiap scope_level, dari yang paling luas (company) ke
     * paling sempit (self). Dipakai untuk mencegah eskalasi privilege: user
     * dengan scope tertentu tidak boleh meng-assign role dengan rank LEBIH
     * TINGGI dari rank scope dia sendiri ke orang lain (lihat
     * App\Concerns\HasScopedAccess::maxOwnScopeRank() &
     * App\Http\Controllers\RoleUserController).
     */
    public const SCOPE_RANK = [
        self::SCOPE_SELF => 0,
        self::SCOPE_DIVISION => 1,
        self::SCOPE_BRANCH => 2,
        self::SCOPE_COMPANY => 3,
    ];

    protected $table = 'roles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'scope_level',
    ];

    /**
     * Users yang memiliki role ini.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'role_user',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Modul/menu yang boleh diakses role ini (pivot role_permission.can_edit
     * menentukan cuma boleh lihat atau boleh kelola juga).
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id')
            ->withPivot('can_edit')
            ->withTimestamps();
    }

    /**
     * Scope role aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}