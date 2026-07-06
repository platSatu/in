<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Http\Request;

class RoleUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            RoleUser::class,
            null,
            ['status'],
            $search,
            10,
            ['user', 'role']
        );

        return view('roleuser.index', compact('data'));
    }

    public function create()
    {
        $users = User::query()->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();

        return view('roleuser.create', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'role_id' => 'required|string|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::create(RoleUser::class, $validated);

        return redirect()
            ->route('roleuser.index')
            ->with('success', 'Role user berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(RoleUser::class, $id, null, ['user', 'role']);
        $users = User::query()->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();

        return view('roleuser.edit', compact('data', 'users', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        AdminCrud::findOrFail(RoleUser::class, $id);

        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'role_id' => 'required|string|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(RoleUser::class, $id, $validated);

        return redirect()
            ->route('roleuser.index')
            ->with('success', 'Role user berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(RoleUser::class, $id);

        return redirect()
            ->route('roleuser.index')
            ->with('success', 'Role user berhasil dihapus.');
    }
}
