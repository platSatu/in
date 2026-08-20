<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\UniversityProfile;
use App\Models\UniversityProfileDegree;
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

    // public function create()
    // {
    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $universities = University::query()
    //         ->where('user_id', (string) $userId)
    //         ->orderBy('name')
    //         ->get();

    //     return view('quiz.university-profile.create', compact('universities'));
    // }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'university_id' => 'required|string|exists:universities,id',
    //         'field' => 'required|string|max:255',
    //         'min_budget' => 'nullable|integer|min:0',
    //         'max_budget' => 'nullable|integer|min:0|gte:min_budget',
    //         'language' => 'nullable|string|max:255',
    //         'scholarship_available' => 'required|boolean',
    //         'status' => 'required|in:active,inactive',
    //         'degree' => 'nullable',
    //         'intake' => 'nullable',
    //     ]);

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $universityOwned = University::query()
    //         ->where('id', $validated['university_id'])
    //         ->where('user_id', (string) $userId)
    //         ->exists();

    //     if (!$universityOwned) {
    //         abort(403, 'University tidak valid untuk user ini.');
    //     }

    //     $validated['user_id'] = (string) $userId;

    //     AdminCrud::create(UniversityProfile::class, $validated);

    //     return redirect()
    //         ->route('quiz.university-profile.index')
    //         ->with('success', 'University Profile berhasil dibuat.');
    // }
public function create(Request $request)
{
    $userId = Auth::id();
    if ($userId === null) {
        abort(401);
    }

    // Eager load 'major' supaya view bisa langsung menampilkan Major dari
    // university yang terkunci (dipakai untuk field "Major" yang di-disable).
    $universities = University::with('major')
        ->where('user_id', (string) $userId)
        ->orderBy('name')
        ->get();

    $selectedUniversityId = $request->query('university_id');

    return view('quiz.university-profile.create', compact('universities', 'selectedUniversityId'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'university_id' => 'required|string|exists:universities,id',
        'min_budget' => 'nullable|integer|min:0',
        'max_budget' => 'nullable|integer|min:0|gte:min_budget',
        'language' => 'nullable|string|max:255',
        'scholarship_available' => 'required|boolean',
        'status' => 'required|in:active,inactive',
        // Degree/Intake sekarang berupa daftar baris ("add row" di form,
        // sama polanya dengan upload foto album) — semuanya opsional karena
        // tidak semua kampus datanya lengkap.
        'degree_intakes' => 'nullable|array',
        'degree_intakes.*.degree' => 'nullable|string|max:255',
        'degree_intakes.*.intake' => 'nullable|string|max:255',
    ]);

    $userId = Auth::id();
    if ($userId === null) {
        abort(401);
    }

    $university = University::with('major')
        ->where('id', $validated['university_id'])
        ->where('user_id', (string) $userId)
        ->first();

    if (!$university) {
        abort(403, 'University tidak valid untuk user ini.');
    }

    // Buang baris degree/intake yang dua-duanya kosong (bukan disimpan
    // sebagai baris kosong).
    $degreeIntakeRows = collect($validated['degree_intakes'] ?? [])
        ->filter(fn ($row) => filled($row['degree'] ?? null) || filled($row['intake'] ?? null))
        ->values();

    unset($validated['degree_intakes']);

    $validated['user_id'] = (string) $userId;

    // "Field" sekarang diturunkan otomatis dari Major yang terpasang di
    // University terpilih (bukan input manual lagi) — dikosongkan (bukan
    // null) kalau University itu belum punya Major, karena kolom `field`
    // di tabel ini NOT NULL tanpa default (lihat kasus serupa
    // `status`/`logo`/`banner` di tabel `universities`).
    // Catatan: tabel `university_profiles` TIDAK punya kolom `degree`/
    // `intake` sama sekali (sempat dikira ada — lihat riwayat error 1054
    // "Unknown column 'degree'"), jadi kedua field itu sengaja tidak diisi
    // di sini; datanya sepenuhnya hidup di tabel `university_profile_degrees`.
    $validated['field'] = optional($university->major)->name ?? '';

    $profile = AdminCrud::create(UniversityProfile::class, $validated);

    foreach ($degreeIntakeRows as $index => $row) {
        UniversityProfileDegree::create([
            'user_id' => (string) $userId,
            'university_profile_id' => $profile->id,
            'degree' => $row['degree'] ?? null,
            'intake' => $row['intake'] ?? null,
            'sort_order' => $index,
        ]);
    }

    // Lanjut ke index Album, modal "Add Album" otomatis kebuka dengan university terkunci.
    return redirect()
        ->route('quiz.university-album.index', ['university_id' => $validated['university_id']])
        ->with('success', 'University Profile berhasil dibuat. Sekarang tambahkan album fotonya.');
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

        // Catatan: 'degree'/'intake' sengaja tidak divalidasi di sini lagi —
        // tabel `university_profiles` tidak punya kolom itu (lihat catatan
        // di store()), datanya sekarang di tabel `university_profile_degrees`.
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
