<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MajorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Major::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10
        );

        return view('quiz.major.index', compact('data'));
    }

    public function create()
    {
        return view('quiz.major.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Major::class, $validated);

        return redirect()
            ->route('quiz.major.index')
            ->with('success', 'Major berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Major::class, $id, (string) $userId);

        return view('quiz.major.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Major::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        AdminCrud::update(Major::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.major.index')
            ->with('success', 'Major berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(Major::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.major.index')
            ->with('success', 'Major berhasil dihapus.');
    }
}