<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Country::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10
        );

        return view('quiz.country.index', compact('data'));
    }

    public function create()
    {
        return view('quiz.country.create');
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

        AdminCrud::create(Country::class, $validated);

        return redirect()
            ->route('country.index')
            ->with('success', 'Country berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Country::class, $id, (string) $userId);

        return view('quiz.country.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Country::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        AdminCrud::update(Country::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('country.index')
            ->with('success', 'Country berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(Country::class, $id, (string) $userId);

        return redirect()
            ->route('country.index')
            ->with('success', 'Country berhasil dihapus.');
    }
}
