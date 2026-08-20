<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\CompanyBranch;
use App\Models\CompanyDivision;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Halaman "Role to User". SEJAK perubahan ini, halaman ini scope-aware:
 * - User dengan role scope_level 'company' (lihat User::isCompanyScoped())
 *   tetap melihat & mengelola SEMUA kombinasi user/role, seperti sebelumnya.
 * - User dengan scope 'branch'/'division'/'self' hanya bisa: (a) melihat &
 *   assign user-user yang ada di branch/divisi yang dia kelola, (b) memilih
 *   role dengan scope_level <= scope dia sendiri (lihat Role::SCOPE_RANK) —
 *   supaya tidak bisa menaikkan orang lain jadi lebih luas dari dirinya
 *   sendiri, (c) memilih Company Branch/Division yang juga masih dalam
 *   cakupannya. Semua ini diberlakukan dobel: disaring di form (create/edit)
 *   DAN divalidasi ulang di server (store/update/destroy) supaya tidak bisa
 *   dikelabui lewat request manual.
 */
class RoleUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $context = $this->scopeContext($request->user());

        $query = RoleUser::query()->with(['user', 'role', 'companyBranch', 'companyDivision']);

        if (!$context['companyScoped']) {
            $query->whereIn('user_id', $this->eligibleUsers($context)->pluck('id'));
        }

        if (!empty($search)) {
            $query->where('status', 'like', "%{$search}%");
        }

        $data = $query->latest('created_at')->paginate(10)->withQueryString();

        return view('roleuser.index', compact('data'));
    }

    public function create(Request $request)
    {
        $context = $this->scopeContext($request->user());

        $users = $this->eligibleUsers($context);
        $roles = $this->eligibleRoles($context);
        $branches = $this->eligibleBranches($context);
        $divisions = $this->eligibleDivisions($context);
        $selectedUserId = $request->query('user_id');
        $selectedRoleId = $request->query('role_id');

        return view('roleuser.create', compact(
            'users',
            'roles',
            'branches',
            'divisions',
            'selectedUserId',
            'selectedRoleId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'string|exists:users,id',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'string|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'company_branch_id' => 'nullable|string|exists:company_branch,id',
            'company_division_id' => 'nullable|string|exists:company_division,id',
        ]);

        $context = $this->scopeContext($request->user());
        $this->assertWithinScope($context, $validated, $validated['user_ids'], $validated['role_ids']);

        $roles = Role::whereIn('id', $validated['role_ids'])->get()->keyBy('id');

        $this->assertScopeSelected($roles, $validated);

        $count = 0;
        foreach ($validated['user_ids'] as $userId) {
            foreach ($validated['role_ids'] as $roleId) {
                RoleUser::updateOrCreate(
                    ['user_id' => $userId, 'role_id' => $roleId],
                    array_merge(
                        ['status' => $validated['status']],
                        $this->scopeFieldsFor($roles[$roleId], $validated)
                    )
                );
                $count++;
            }
        }

        return redirect()
            ->route('roleuser.index')
            ->with('success', "Berhasil menyimpan {$count} kombinasi role user.");
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(RoleUser::class, $id, null, ['user', 'role']);
        $context = $this->scopeContext(request()->user());
        $this->assertCanManageRow($data, $context);

        $users = $this->eligibleUsers($context);
        $roles = $this->eligibleRoles($context);
        $branches = $this->eligibleBranches($context);
        $divisions = $this->eligibleDivisions($context);

        return view('roleuser.edit', compact('data', 'users', 'roles', 'branches', 'divisions'));
    }

    public function update(Request $request, string $id)
    {
        $existing = AdminCrud::findOrFail(RoleUser::class, $id);
        $context = $this->scopeContext($request->user());
        $this->assertCanManageRow($existing, $context);

        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'role_id' => 'required|string|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'company_branch_id' => 'nullable|string|exists:company_branch,id',
            'company_division_id' => 'nullable|string|exists:company_division,id',
        ]);

        $this->assertWithinScope($context, $validated, [$validated['user_id']], [$validated['role_id']]);

        $role = Role::findOrFail($validated['role_id']);
        $roles = collect([$role->id => $role]);

        $this->assertScopeSelected($roles, $validated);

        $payload = array_merge(
            [
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role_id'],
                'status' => $validated['status'],
            ],
            $this->scopeFieldsFor($role, $validated)
        );

        AdminCrud::update(RoleUser::class, $id, $payload);

        return redirect()
            ->route('roleuser.index')
            ->with('success', 'Role user berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $existing = AdminCrud::findOrFail(RoleUser::class, $id);
        $context = $this->scopeContext(request()->user());
        $this->assertCanManageRow($existing, $context);

        AdminCrud::delete(RoleUser::class, $id);

        return redirect()
            ->route('roleuser.index')
            ->with('success', 'Role user berhasil dihapus.');
    }

    /**
     * Pastikan company_branch_id/company_division_id sudah dipilih kalau ada
     * salah satu role yang di-assign butuh scope tsb (scope_level branch atau
     * division). Role dengan scope_level company/self tidak butuh keduanya
     * dan tidak divalidasi di sini.
     *
     * @param \Illuminate\Support\Collection<string, Role> $roles
     */
    private function assertScopeSelected($roles, array $validated): void
    {
        $needsBranch = $roles->contains(fn (Role $role) => $role->scope_level === Role::SCOPE_BRANCH);
        $needsDivision = $roles->contains(fn (Role $role) => $role->scope_level === Role::SCOPE_DIVISION);

        $errors = [];

        if ($needsBranch && empty($validated['company_branch_id'])) {
            $errors['company_branch_id'] = 'Company Branch wajib dipilih untuk role dengan scope Branch.';
        }

        if ($needsDivision && empty($validated['company_division_id'])) {
            $errors['company_division_id'] = 'Division / Unit wajib dipilih untuk role dengan scope Division.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tentukan company_branch_id/company_division_id yang disimpan di baris
     * role_user, KHUSUS untuk role tsb (bukan asal isi keduanya) — supaya
     * assign banyak role sekaligus dengan scope_level berbeda-beda tetap
     * konsisten (role scope company/self tidak ikut kebawa branch/division
     * yang dipilih di form, itu cuma relevan buat role branch/division).
     */
    private function scopeFieldsFor(Role $role, array $validated): array
    {
        return match ($role->scope_level) {
            Role::SCOPE_BRANCH => [
                'company_branch_id' => $validated['company_branch_id'],
                'company_division_id' => null,
            ],
            Role::SCOPE_DIVISION => [
                'company_branch_id' => null,
                'company_division_id' => $validated['company_division_id'],
            ],
            default => [
                'company_branch_id' => null,
                'company_division_id' => null,
            ],
        };
    }

    /**
     * Ringkasan cakupan akses user yang sedang login, dipakai bareng oleh
     * index/create/store/edit/update/destroy supaya aturannya konsisten di
     * satu tempat:
     * - companyScoped=true  : tidak ada batasan sama sekali (perilaku lama).
     * - branchIds            : id company_branch yang dia kelola.
     * - divisionIds           : id company_division yang dia kelola — kalau
     *   dia branch-scoped, ini otomatis diisi SEMUA divisi di branch2 tsb.
     * - maxRank               : rank scope paling luas dari role aktifnya
     *   sendiri (lihat Role::SCOPE_RANK) — batas atas scope role yang boleh
     *   dia assign-kan ke orang lain.
     */
    private function scopeContext(User $actor): array
    {
        if ($actor->isCompanyScoped()) {
            return [
                'companyScoped' => true,
                'branchIds' => null,
                'divisionIds' => null,
                'maxRank' => Role::SCOPE_RANK[Role::SCOPE_COMPANY],
            ];
        }

        $branchIds = $actor->visibleBranchIds() ?? [];
        $divisionIds = $actor->visibleDivisionIds();

        if ($divisionIds === null) {
            // Branch-scoped: cakupannya otomatis semua divisi di branch2 itu.
            $divisionIds = empty($branchIds)
                ? []
                : CompanyDivision::whereIn('company_branch_id', $branchIds)->pluck('id')->all();
        }

        return [
            'companyScoped' => false,
            'branchIds' => $branchIds,
            'divisionIds' => $divisionIds,
            'maxRank' => $actor->maxOwnScopeRank(),
        ];
    }

    /**
     * User yang boleh dipilih di form (dan boleh ditampilkan di index) —
     * kalau tidak company-scoped, dibatasi ke user yang terdaftar di salah
     * satu divisi dalam cakupan aktor (lihat App\Models\User::divisions(),
     * diisi via halaman Company > Division > Add User).
     */
    private function eligibleUsers(array $context): Collection
    {
        if ($context['companyScoped']) {
            return User::query()->orderBy('name')->get();
        }

        if (empty($context['divisionIds'])) {
            return collect();
        }

        return User::whereHas('divisions', function ($query) use ($context) {
            $query->whereIn('company_division.id', $context['divisionIds']);
        })->orderBy('name')->get();
    }

    /**
     * Role yang boleh di-assign — dibatasi ke scope_level dengan rank <=
     * rank scope aktor sendiri (Role::SCOPE_RANK), supaya aktor tidak bisa
     * menjadikan orang lain lebih luas cakupannya dari dirinya sendiri.
     */
    private function eligibleRoles(array $context): Collection
    {
        if ($context['companyScoped']) {
            return Role::query()->orderBy('name')->get();
        }

        $maxRank = $context['maxRank'] ?? -1;
        $allowedLevels = collect(Role::SCOPE_RANK)
            ->filter(fn ($rank) => $rank <= $maxRank)
            ->keys()
            ->all();

        if (empty($allowedLevels)) {
            return collect();
        }

        return Role::whereIn('scope_level', $allowedLevels)->orderBy('name')->get();
    }

    private function eligibleBranches(array $context): Collection
    {
        if ($context['companyScoped']) {
            return CompanyBranch::query()->orderBy('name')->get();
        }

        if (empty($context['branchIds'])) {
            return collect();
        }

        return CompanyBranch::whereIn('id', $context['branchIds'])->orderBy('name')->get();
    }

    private function eligibleDivisions(array $context): Collection
    {
        if ($context['companyScoped']) {
            return CompanyDivision::query()->orderBy('name')->get();
        }

        if (empty($context['divisionIds'])) {
            return collect();
        }

        return CompanyDivision::whereIn('id', $context['divisionIds'])->orderBy('name')->get();
    }

    /**
     * Validasi server-side (bukan cuma sembunyikan opsi di form): pastikan
     * seluruh user_ids/role_ids/branch/division yang benar-benar dikirim
     * request ada di dalam cakupan aktor. Dilewati kalau aktor company-scoped.
     */
    private function assertWithinScope(array $context, array $validated, array $userIds, array $roleIds): void
    {
        if ($context['companyScoped']) {
            return;
        }

        $eligibleUserIds = $this->eligibleUsers($context)->pluck('id')->all();
        $eligibleRoleIds = $this->eligibleRoles($context)->pluck('id')->all();

        $errors = [];

        if (!empty(array_diff($userIds, $eligibleUserIds))) {
            $errors['user_ids'] = 'Ada user yang berada di luar cakupan akses Anda.';
        }

        if (!empty(array_diff($roleIds, $eligibleRoleIds))) {
            $errors['role_ids'] = 'Ada role yang berada di luar cakupan akses Anda (scope-nya lebih luas dari scope Anda sendiri).';
        }

        if (!empty($validated['company_branch_id'])
            && !in_array($validated['company_branch_id'], $context['branchIds'] ?? [], true)) {
            $errors['company_branch_id'] = 'Company Branch tsb berada di luar cakupan akses Anda.';
        }

        if (!empty($validated['company_division_id'])
            && !in_array($validated['company_division_id'], $context['divisionIds'] ?? [], true)) {
            $errors['company_division_id'] = 'Division / Unit tsb berada di luar cakupan akses Anda.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Guard untuk edit/update/destroy 1 baris role_user yang sudah ada:
     * aktor non-company-scoped hanya boleh mengelola baris yang user
     * targetnya ada dalam cakupannya (mencegah akses lewat tebak-tebak URL
     * /roleuser/{id}/edit ke baris milik branch/divisi lain).
     */
    private function assertCanManageRow(RoleUser $row, array $context): void
    {
        if ($context['companyScoped']) {
            return;
        }

        $eligibleUserIds = $this->eligibleUsers($context)->pluck('id')->all();

        if (!in_array($row->user_id, $eligibleUserIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke data role user ini.');
        }
    }
}
