<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\SettingUniversity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingUniversityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = SettingUniversity::with([
            'city',
            'major',
            'university'
        ])
            ->where('user_id', (string) $userId)
            ->when($search, function ($query) use ($search) {
                $query->whereHas('city', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('major', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('university', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->paginate(10);

        return view('quiz.setting-university.index', compact('data'));
    }

    public function create()
    {
        $cities = \App\Models\City::where('status', 'active')->get();
        $majors = \App\Models\Major::where('status', 'active')->get();
        $universities = \App\Models\University::where('status', 'active')->get();

        return view('quiz.setting-university.create', compact(
            'cities',
            'majors',
            'universities'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|string|max:36',
            'major_id' => 'required|string|max:36',
            'university_id' => 'required|string|max:36',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(SettingUniversity::class, $validated);

        return redirect()
            ->route('quiz.setting-university.index')
            ->with('success', 'Setting University berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();

        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(
            SettingUniversity::class,
            $id,
            (string) $userId
        );

        $cities = \App\Models\City::where('status', 'active')->get();
        $majors = \App\Models\Major::where('status', 'active')->get();
        $universities = \App\Models\University::where('status', 'active')->get();

        return view('quiz.setting-university.edit', compact(
            'data',
            'cities',
            'majors',
            'universities'
        ));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(
            SettingUniversity::class,
            $id,
            (string) $userId
        );

        $validated = $request->validate([
            'city_id' => 'required|string|max:36',
            'major_id' => 'required|string|max:36',
            'university_id' => 'required|string|max:36',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(
            SettingUniversity::class,
            $id,
            $validated,
            (string) $userId
        );

        return redirect()
            ->route('quiz.setting-university.index')
            ->with('success', 'Setting University berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(
            SettingUniversity::class,
            $id,
            (string) $userId
        );

        return redirect()
            ->route('quiz.setting-university.index')
            ->with('success', 'Setting University berhasil dihapus.');
    }
}