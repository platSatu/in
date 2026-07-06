<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            University::class,
            (string) $userId,
            ['name', 'country', 'city', 'description'],
            $search,
            10
        );

        return view('quiz.university.index', compact('data'));
    }

    public function create()
    {
        return view('quiz.university.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(University::class, $validated);

        return redirect()
            ->route('quiz.university.index')
            ->with('success', 'University berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(University::class, $id, (string) $userId);

        return view('quiz.university.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(University::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        AdminCrud::update(University::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.university.index')
            ->with('success', 'University berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(University::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.university.index')
            ->with('success', 'University berhasil dihapus.');
    }
}
