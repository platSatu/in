<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\HistoryUserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryUserLoginController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            HistoryUserLogin::class,
            null,
            ['name', 'email', 'duration'],
            $search,
            10,
            ['user'],
            'last_login'
        );

        return view('historyuserlogin.index', compact('data'));
    }

    public function create()
    {
        return view('historyuserlogin.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_login' => 'nullable|date',
            'last_logout' => 'nullable|date|after_or_equal:last_login',
            'duration' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(HistoryUserLogin::class, $validated);

        return redirect()
            ->route('historyuserlogin.index')
            ->with('success', 'History login berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(HistoryUserLogin::class, $id, (string) $userId);

        return view('historyuserlogin.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(HistoryUserLogin::class, $id, (string) $userId);

        $validated = $request->validate([
            'last_login' => 'nullable|date',
            'last_logout' => 'nullable|date|after_or_equal:last_login',
            'duration' => 'nullable|string|max:255',
        ]);

        AdminCrud::update(HistoryUserLogin::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('historyuserlogin.index')
            ->with('success', 'History login berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(HistoryUserLogin::class, $id, (string) $userId);

        return redirect()
            ->route('historyuserlogin.index')
            ->with('success', 'History login berhasil dihapus.');
    }
}
