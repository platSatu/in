<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            Role::class,
            null,
            ['name', 'slug', 'status'],
            $search,
            10
        );

        return view('roles.index', compact('data'));
    }

    public function create()
    {
        $permissionGroups = $this->permissionGroups();

        return view('roles.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $permissionIds = $validated['permissions'] ?? [];
        $editIds = $validated['can_edit'] ?? [];
        unset($validated['permissions'], $validated['can_edit']);

        $role = AdminCrud::create(Role::class, $validated);

        $this->syncPermissions($role, $permissionIds, $editIds);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function show(string $id)
    {
        $data = AdminCrud::findOrFail(Role::class, $id);
        $users = $data->users()->orderBy('name')->paginate(10);

        return view('roles.show', compact('data', 'users'));
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(Role::class, $id, null, ['permissions']);
        $permissionGroups = $this->permissionGroups();
        $selectedPermissionIds = $data->permissions->pluck('id')->all();
        $selectedEditIds = $data->permissions->filter(fn ($p) => (bool) $p->pivot->can_edit)->pluck('id')->all();

        return view('roles.edit', compact('data', 'permissionGroups', 'selectedPermissionIds', 'selectedEditIds'));
    }

    public function update(Request $request, string $id)
    {
        $data = AdminCrud::findOrFail(Role::class, $id);

        $validated = $this->validatePayload($request, $data->id);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $permissionIds = $validated['permissions'] ?? [];
        $editIds = $validated['can_edit'] ?? [];
        unset($validated['permissions'], $validated['can_edit']);

        $role = AdminCrud::update(Role::class, $id, $validated);

        $this->syncPermissions($role, $permissionIds, $editIds);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(Role::class, $id);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Validasi field dasar role + scope_level + daftar permission yang dipilih.
     * Dipakai bareng oleh store() dan update() supaya aturan tidak dobel-tulis.
     */
    private function validatePayload(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255|unique:roles,name' . ($ignoreId ? ",{$ignoreId}" : ''),
            'slug' => 'nullable|string|max:255|unique:roles,slug' . ($ignoreId ? ",{$ignoreId}" : ''),
            'status' => 'required|in:active,inactive',
            'scope_level' => 'required|in:' . implode(',', Role::SCOPE_LEVELS),
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,id',
            'can_edit' => 'nullable|array',
            'can_edit.*' => 'string|exists:permissions,id',
        ]);
    }

    /**
     * Simpan pilihan akses menu role ke pivot role_permission. Sebuah
     * permission yang cuma dicentang "Kelola" (can_edit) tapi tidak dicentang
     * "Lihat" tetap dianggap butuh akses lihat juga (union), supaya admin
     * tidak perlu centang 2x untuk kasus yang jelas-jelas butuh keduanya —
     * lihat App\Concerns\HasScopedAccess::canAccessPermission() yang
     * memperlakukan ability 'view' sebagai "role ini punya baris permission
     * ini, apapun nilai can_edit-nya".
     */
    private function syncPermissions(Role $role, array $permissionIds, array $editIds): void
    {
        $allIds = array_values(array_unique(array_merge($permissionIds, $editIds)));

        $syncData = collect($allIds)->mapWithKeys(function ($id) use ($editIds) {
            return [$id => ['can_edit' => in_array($id, $editIds, true)]];
        })->all();

        $role->permissions()->sync($syncData);
    }

    /**
     * Katalog permission (dari config/menu.php lewat tabel permissions),
     * dikelompokkan per group_label untuk ditampilkan sbg checklist di form
     * Role. Urut sesuai sort_order (= urutan asli di config/menu.php).
     */
    private function permissionGroups()
    {
        return Permission::orderBy('sort_order')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->group_label ?? 'Lainnya');
    }
}
