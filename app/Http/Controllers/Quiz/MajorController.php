<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\City;
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
            10,
            ['city']
        );

        return view('quiz.major.index', compact('data'));
    }

    public function create(Request $request)
    {
        $cities = City::orderBy('name')->get();
        $selectedCityId = $request->query('city_id');

        return view('quiz.major.create', compact('cities', 'selectedCityId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'nullable|exists:cities,id',
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

        $data = AdminCrud::findOrFail(Major::class, $id, (string) $userId, ['city']);
        $cities = City::orderBy('name')->get();

        return view('quiz.major.edit', compact('data', 'cities'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Major::class, $id, (string) $userId);

        $validated = $request->validate([
            'city_id' => 'nullable|exists:cities,id',
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
