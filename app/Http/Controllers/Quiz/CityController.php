<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            City::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10,
            ['country']
        );

        return view('quiz.city.index', compact('data'));
    }

    public function create(Request $request)
    {
        $countries = Country::orderBy('name')->get();
        $selectedCountryId = $request->query('country_id');

        return view('quiz.city.create', compact('countries', 'selectedCountryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'nullable|exists:countries,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(City::class, $validated);

        return redirect()
            ->route('city.index')
            ->with('success', 'Kota berhasil dibuat.');
    }

    public function show(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(City::class, $id, (string) $userId, ['country']);
        $data->load(['universities' => function ($query) {
            $query->orderBy('name');
        }]);

        return view('quiz.city.show', compact('data'));
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(City::class, $id, (string) $userId, ['country']);
        $countries = Country::orderBy('name')->get();

        return view('quiz.city.edit', compact('data', 'countries'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(City::class, $id, (string) $userId);

        $validated = $request->validate([
            'country_id' => 'nullable|exists:countries,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        AdminCrud::update(City::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('city.index')
            ->with('success', 'Kota berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(City::class, $id, (string) $userId);

        return redirect()
            ->route('city.index')
            ->with('success', 'Kota berhasil dihapus.');
    }
}
