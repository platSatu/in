<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\UniversityAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversityAlbumController extends Controller
{
    // public function index(Request $request)
    // {
    //     $search = $request->query('search');

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $data = AdminCrud::paginate(
    //         UniversityAlbum::class,
    //         (string) $userId,
    //         ['name', 'description'],
    //         $search,
    //         10
    //     );

    //     return view('quiz.university-album.index', compact('data'));
    // }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $universityId = $request->query('university_id');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // AdminCrud::paginate() tidak punya opsi filter tambahan (selain
        // ownership + search), jadi query-nya dibangun manual di sini supaya
        // bisa di-scope per university_id — dipakai saat index ini dibuka
        // dari halaman profile University ("Manage Album"), bukan dari menu
        // global. Sebelumnya university_id diterima tapi tidak pernah
        // dipakai untuk filter sama sekali (dead scoping).
        // Tidak lagi dibatasi ->where('user_id', ...) -- album boleh dilihat
        // admin manapun, tidak cuma pembuatnya.
        $query = UniversityAlbum::query();

        if (!empty($universityId)) {
            $query->where('university_id', $universityId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $data = $query->with('university')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $universities = University::orderBy('name')->get();

        // Konteks University (kalau index ini dibuka scoped) — dipakai untuk
        // breadcrumb + tombol "Back to Profile" + mengunci university_id di
        // tombol "+ Add University Album".
        $university = !empty($universityId)
            ? University::where('id', $universityId)->first()
            : null;

        return view('quiz.university-album.index', compact('data', 'universities', 'university', 'universityId'));
    }

    // public function create()
    // {
    //     $universities = University::orderBy('name')->get();

    //     return view('quiz.university-album.create', compact('universities'));
    // }
    public function create(Request $request)
    {
        $universities = University::orderBy('name')->get();
        $selectedUniversityId = $request->query('university_id');
    
        return view('quiz.university-album.create', compact('universities', 'selectedUniversityId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'university_id' => 'required|exists:universities,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(UniversityAlbum::class, $validated);

        // Kembali ke album index yang sudah di-scope ke university itu
        // (bukan index global) — sesuai alur: create album -> kembali ke
        // album dari university itu.
        return redirect()
            ->route('quiz.university-album.index', ['university_id' => $validated['university_id']])
            ->with('success', 'University Album berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(UniversityAlbum::class, $id, null);
        $universities = University::orderBy('name')->get();

        return view('quiz.university-album.edit', compact('data', 'universities'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(UniversityAlbum::class, $id, null);

        $validated = $request->validate([
            'university_id' => 'required|exists:universities,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(UniversityAlbum::class, $id, $validated, null);

        return redirect()
            ->route('quiz.university-album.index', ['university_id' => $validated['university_id']])
            ->with('success', 'University Album berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Ambil dulu university_id-nya sebelum dihapus, supaya redirect
        // tetap kembali ke album index yang scoped ke university itu.
        $existing = AdminCrud::findOrFail(UniversityAlbum::class, $id, null);
        $universityId = $existing->university_id;

        AdminCrud::delete(UniversityAlbum::class, $id, null);

        return redirect()
            ->route('quiz.university-album.index', ['university_id' => $universityId])
            ->with('success', 'University Album berhasil dihapus.');
    }
}