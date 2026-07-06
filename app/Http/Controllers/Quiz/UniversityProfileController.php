<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\UniversityProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversityProfileController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            UniversityProfile::class,
            (string) $userId,
            ['field', 'language', 'status'],
            $search,
            10,
            ['university']
        );

        return view('quiz.university-profile.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $universities = University::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('quiz.university-profile.create', compact('universities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'university_id' => 'required|string|exists:universities,id',
            'field' => 'required|string|max:255',
            'min_budget' => 'nullable|integer|min:0',
            'max_budget' => 'nullable|integer|min:0|gte:min_budget',
            'language' => 'nullable|string|max:255',
            'scholarship_available' => 'required|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $universityOwned = University::query()
            ->where('id', $validated['university_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$universityOwned) {
            abort(403, 'University tidak valid untuk user ini.');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(UniversityProfile::class, $validated);

        return redirect()
            ->route('quiz.university-profile.index')
            ->with('success', 'University Profile berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(UniversityProfile::class, $id, (string) $userId, ['university']);

        $universities = University::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('quiz.university-profile.edit', compact('data', 'universities'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(UniversityProfile::class, $id, (string) $userId);

        $validated = $request->validate([
            'university_id' => 'required|string|exists:universities,id',
            'field' => 'required|string|max:255',
            'min_budget' => 'nullable|integer|min:0',
            'max_budget' => 'nullable|integer|min:0|gte:min_budget',
            'language' => 'nullable|string|max:255',
            'scholarship_available' => 'required|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $universityOwned = University::query()
            ->where('id', $validated['university_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$universityOwned) {
            abort(403, 'University tidak valid untuk user ini.');
        }

        AdminCrud::update(UniversityProfile::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.university-profile.index')
            ->with('success', 'University Profile berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(UniversityProfile::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.university-profile.index')
            ->with('success', 'University Profile berhasil dihapus.');
    }
}
