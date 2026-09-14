<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

        $user = AdminCrud::create(User::class, $validated);

        // FIX (permintaan user, 14 September 2026): akun yang dibuat manual dari
        // sini (superadmin bikin user staff/sales langsung lewat menu Users)
        // TIDAK PERNAH memicu event Registered seperti alur daftar sendiri di
        // Auth\RegisteredUserController -- jadi tidak ada email verifikasi
        // yang benar-benar terkirim. Kalau dibiarkan, email_verified_at tetap
        // null dan user ini akan MENTOK SELAMANYA di halaman "verifikasi email
        // dulu" begitu coba login (tidak akan pernah bisa verifikasi karena
        // link verifikasinya memang tidak pernah dikirim sama sekali). Sama
        // persis pola fix yang sudah ada di
        // App\Http\Controllers\Student\StudentController::addUser() -- karena
        // akun ini memang sengaja dibuat admin (bukan daftar sendiri), tandai
        // langsung terverifikasi di sini.
        $user->markEmailAsVerified();

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
            // Fix (14 September 2026, permintaan user): Kode Sales normalnya
            // dibuat OTOMATIS oleh sistem saat role "sales" di-assign (lihat
            // RoleUserController::assignSalesCodeIfNeeded()), tapi superadmin
            // tetap boleh mengganti manual dari sini kalau perlu.
            'sales_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'sales_code')->ignore($data->id)],
            'status' => 'required|in:active,inactive',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if (empty($validated['sales_code'])) {
            $validated['sales_code'] = null;
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
