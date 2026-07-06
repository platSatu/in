<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
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
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        AdminCrud::create(Role::class, $validated);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(Role::class, $id);

        return view('roles.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $data = AdminCrud::findOrFail(Role::class, $id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $data->id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $data->id,
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        AdminCrud::update(Role::class, $id, $validated);

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
}
