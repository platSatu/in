<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\SettingUniversity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingUniversityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Tidak lagi dibatasi ->where('user_id', ...) -- boleh dilihat admin manapun.
        $data = SettingUniversity::with([
            'city',
            'major',
            'university'
        ])
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
            null
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
            null
        );

        $validated = $request->validate([
            'city_id' => 'required|string|exists:cities,id',
            'major_id' => 'required|string|exists:majors,id',
            'university_id' => 'required|string|exists:universities,id',
            'status' => 'required|in:active,inactive',
            // Baris tambahan: university lain yang ikut dihubungkan ke major yang sama.
            'extra_university_ids' => 'nullable|array|max:50',
            'extra_university_ids.*' => 'nullable|string|distinct|exists:universities,id',
        ], [
            'extra_university_ids.*.distinct' => 'University yang sama dipilih lebih dari sekali.',
            'extra_university_ids.*.exists' => 'University yang dipilih tidak ditemukan.',
        ]);

        $extraIds = collect($validated['extra_university_ids'] ?? [])
            ->filter()
            ->reject(fn ($universityId) => $universityId === $validated['university_id'])
            ->unique()
            ->values();
        unset($validated['extra_university_ids']);

        $added = DB::transaction(function () use ($id, $validated, $extraIds, $userId) {
            AdminCrud::update(SettingUniversity::class, $id, $validated, null);

            if ($extraIds->isEmpty()) {
                return 0;
            }

            // Yang sudah terhubung ke major ini dilewati, supaya tidak ada pasangan dobel.
            $alreadyLinked = SettingUniversity::where('major_id', $validated['major_id'])
                ->whereIn('university_id', $extraIds)
                ->pluck('university_id');

            $newIds = $extraIds->diff($alreadyLinked);

            foreach ($newIds as $universityId) {
                AdminCrud::create(SettingUniversity::class, [
                    'user_id' => (string) $userId,
                    'city_id' => $validated['city_id'],
                    'major_id' => $validated['major_id'],
                    'university_id' => $universityId,
                    'status' => $validated['status'],
                ]);
            }

            return $newIds->count();
        });

        $message = 'Setting University berhasil diupdate.';
        if ($added > 0) {
            $message .= " {$added} university baru ikut dihubungkan ke major ini.";
        }
        if ($extraIds->count() > $added) {
            $message .= ' ' . ($extraIds->count() - $added) . ' university dilewati karena sudah terhubung.';
        }

        return redirect()
            ->route('quiz.setting-university.index')
            ->with('success', $message);
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
            null
        );

        return redirect()
            ->route('quiz.setting-university.index')
            ->with('success', 'Setting University berhasil dihapus.');
    }
}