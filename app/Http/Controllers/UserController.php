<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            User::class,
            null,
            ['name', 'email', 'handphone', 'status'],
            $search,
            10
        );

        return view('users.index', compact('data'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'handphone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::create(User::class, $validated);

        return redirect()
            ->route('user.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(User::class, $id);

        return view('users.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $data = AdminCrud::findOrFail(User::class, $id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $data->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'handphone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        AdminCrud::update(User::class, $id, $validated);

        return redirect()
            ->route('user.index')
            ->with('success', 'User berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(User::class, $id);

        return redirect()
            ->route('user.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
