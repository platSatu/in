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
    
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }
    
        $data = AdminCrud::paginate(
            UniversityAlbum::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10
        );
    
        $universities = University::orderBy('name')->get();
    
        return view('quiz.university-album.index', compact('data', 'universities'));
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

        return redirect()
            ->route('quiz.university-album.index')
            ->with('success', 'University Album berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(UniversityAlbum::class, $id, (string) $userId);
        $universities = University::orderBy('name')->get();

        return view('quiz.university-album.edit', compact('data', 'universities'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(UniversityAlbum::class, $id, (string) $userId);

        $validated = $request->validate([
            'university_id' => 'required|exists:universities,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(UniversityAlbum::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.university-album.index')
            ->with('success', 'University Album berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(UniversityAlbum::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.university-album.index')
            ->with('success', 'University Album berhasil dihapus.');
    }
}