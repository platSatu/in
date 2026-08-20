<?php

namespace App\Concerns;

use App\Models\CompanyDivision;
use App\Models\Role;
use App\Models\RoleUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Dipakai di App\Models\User. Satu tempat untuk semua logika "data apa yang
 * boleh dilihat/diakses user ini", supaya controller-controller tinggal
 * panggil method di sini, bukan menulis ulang query scope-nya sendiri-sendiri.
 *
 * Konsep scope_level (lihat migration add_scope_level_to_roles_table):
 * - company  : tidak dibatasi sama sekali (lihat semua branch & divisi).
 * - branch   : dibatasi ke branch tempat role di-assign (role_user.company_branch_id),
 *              otomatis mencakup SEMUA divisi di branch itu.
 * - division : dibatasi ke divisi tempat role di-assign (role_user.company_division_id).
 * - self     : tidak dibatasi by branch/division sama sekali, tapi ke data yang
 *              ditangani user itu sendiri (mis. Student.handled_by_user_id) —
 *              lihat isSelfScopedOnly(), penerapannya per-modul di controller
 *              masing-masing karena kolom "milik sendiri" beda-beda tiap modul.
 */
trait HasScopedAccess
{
    protected ?Collection $activeRoleAssignmentsCache = null;

    public function roleUserRows(): HasMany
    {
        return $this->hasMany(RoleUser::class, 'user_id');
    }

    /**
     * Baris role_user user ini yang statusnya aktif DAN role-nya juga aktif,
     * beserta permission role tsb (di-cache per instance biar tidak query ulang).
     */
    public function activeRoleAssignments(): Collection
    {
        if ($this->activeRoleAssignmentsCache === null) {
            $this->activeRoleAssignmentsCache = $this->roleUserRows()
                ->with('role.permissions')
                ->where('status', RoleUser::STATUS_ACTIVE)
                ->get()
                ->filter(fn (RoleUser $ru) => $ru->role && $ru->role->status === Role::STATUS_ACTIVE)
                ->values();
        }

        return $this->activeRoleAssignmentsCache;
    }

    public function isCompanyScoped(): bool
    {
        return $this->activeRoleAssignments()
            ->contains(fn (RoleUser $ru) => $ru->role->scope_level === Role::SCOPE_COMPANY);
    }

    protected function isBranchScoped(): bool
    {
        return $this->activeRoleAssignments()
            ->contains(fn (RoleUser $ru) => $ru->role->scope_level === Role::SCOPE_BRANCH);
    }

    /**
     * ID company_division yang secara eksplisit di-assign (scope_level = division).
     */
    protected function scopedDivisionIds(): array
    {
        return $this->activeRoleAssignments()
            ->filter(fn (RoleUser $ru) => $ru->role->scope_level === Role::SCOPE_DIVISION)
            ->pluck('company_division_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * ID company_branch yang boleh dilihat user ini.
     * null = TIDAK DIBATASI (company-wide, jangan di-filter sama sekali).
     */
    public function visibleBranchIds(): ?array
    {
        if ($this->isCompanyScoped()) {
            return null;
        }

        $directBranchIds = $this->activeRoleAssignments()
            ->filter(fn (RoleUser $ru) => $ru->role->scope_level === Role::SCOPE_BRANCH)
            ->pluck('company_branch_id')
            ->filter();

        $divisionIds = $this->scopedDivisionIds();
        $branchIdsFromDivisions = empty($divisionIds)
            ? collect()
            : CompanyDivision::whereIn('id', $divisionIds)->pluck('company_branch_id');

        return $directBranchIds->merge($branchIdsFromDivisions)->unique()->values()->all();
    }

    /**
     * ID company_division yang boleh dilihat user ini.
     * null = TIDAK DIBATASI ke divisi tertentu (company-wide ATAU branch-wide —
     * branch-wide otomatis mencakup semua divisi di branch itu, lihat
     * visibleBranchIds() yang harus dipakai bareng untuk kasus ini).
     */
    public function visibleDivisionIds(): ?array
    {
        if ($this->isCompanyScoped() || $this->isBranchScoped()) {
            return null;
        }

        return $this->scopedDivisionIds();
    }

    /**
     * True kalau SATU-SATUNYA scope aktif user ini 'self' (mis. marketing/
     * pengajar) — dipakai modul seperti Student utk filter ke data yang dia
     * tangani sendiri. Kalau user juga punya role company/branch/division di
     * modul yang sama, itu yang menang (lebih luas), jadi bukan self-only.
     */
    public function isSelfScopedOnly(): bool
    {
        $levels = $this->activeRoleAssignments()
            ->pluck('role.scope_level')
            ->unique();

        return $levels->isNotEmpty() && $levels->diff([Role::SCOPE_SELF])->isEmpty();
    }

    /**
     * Cek apakah user berhak akses modul (permission key) tertentu.
     * $ability 'view' = cukup salah satu role-nya punya permission itu.
     * $ability 'edit' = salah satu role-nya punya permission itu DENGAN can_edit=true.
     */
    public function canAccessPermission(string $permissionKey, string $ability = 'view'): bool
    {
        return $this->activeRoleAssignments()
            ->pluck('role')
            ->filter()
            ->flatMap(fn (Role $role) => $role->permissions)
            ->filter(fn ($permission) => $permission->key === $permissionKey)
            ->contains(fn ($permission) => $ability !== 'edit' || (bool) $permission->pivot->can_edit);
    }

    /**
     * Rank scope PALING LUAS dari seluruh role aktif user ini (lihat
     * Role::SCOPE_RANK). Dipakai untuk mencegah eskalasi privilege di halaman
     * Role to User: user tidak boleh meng-assign role dengan rank lebih
     * tinggi dari rank scope dia sendiri ke orang lain — kalau tidak, user
     * scope 'division' bisa saja menjadikan orang lain scope 'company'.
     * Null kalau user tidak punya role aktif sama sekali (paling sempit).
     */
    public function maxOwnScopeRank(): ?int
    {
        return $this->activeRoleAssignments()
            ->map(fn (RoleUser $ru) => Role::SCOPE_RANK[$ru->role->scope_level] ?? null)
            ->filter(fn ($rank) => $rank !== null)
            ->max();
    }
}
