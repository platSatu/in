<?php
/*
|--------------------------------------------------------------------------
| CATATAN
|--------------------------------------------------------------------------
| - Sama pola-nya dengan UniversityAlbumController, bedanya store() menerima
|   BANYAK foto sekaligus (array 'photos'), sesuai permintaan "add row" di
|   halaman create. Tiap baris = 1 file foto + title + description + sort_order,
|   semuanya masuk ke album yang sama (dipilih sekali di atas form).
| - File foto disimpan LANGSUNG ke folder public/university (bukan lewat
|   Storage::disk('public') / symlink), sesuai permintaan. Kolom `photo`
|   di database menyimpan path relatif, misal "university/xxxx.jpg", supaya
|   gampang dipanggil pakai asset($photo->photo) di blade.
| - Model UniversityAlbumPhoto sebaiknya punya relasi:
|       public function album() { return $this->belongsTo(UniversityAlbum::class, 'album_id'); }
|   dipakai di index untuk menampilkan nama album.
*/

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\UniversityAlbum;
use App\Models\UniversityAlbumPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UniversityAlbumPhotoController extends Controller
{
    /**
     * Folder tujuan penyimpanan foto, relatif terhadap public_path().
     */
    private string $uploadFolder = 'university';

    // public function index(Request $request)
    // {
    //     $search = $request->query('search');

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $data = AdminCrud::paginate(
    //         UniversityAlbumPhoto::class,
    //         (string) $userId,
    //         ['title', 'description'],
    //         $search,
    //         10
    //     );

    //     return view('quiz.university-album-photo.index', compact('data'));
    // }
    public function index(Request $request)
    {
        $search = $request->query('search');
        $albumId = $request->query('album_id');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Sama seperti UniversityAlbumController::index() — query dibangun
        // manual (bukan lewat AdminCrud::paginate()) supaya bisa di-scope ke
        // album_id. Sebelumnya album_id (dulu malah dibaca sebagai
        // university_id, tidak dipakai sama sekali) tidak pernah dipakai
        // untuk filter (dead scoping), dan kolom search-nya salah ('name',
        // padahal tabel ini kolomnya 'title') — dua-duanya dibetulkan di sini.
        // Tidak lagi dibatasi ->where('user_id', ...) -- foto album boleh
        // dilihat admin manapun, tidak cuma pembuatnya.
        $query = UniversityAlbumPhoto::query();

        if (!empty($albumId)) {
            $query->where('album_id', $albumId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $data = $query->with('album.university')
            ->orderBy('sort_order')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        // Konteks Album (kalau index ini dibuka scoped) — dipakai untuk
        // breadcrumb + tombol "Back to Album" + mengunci album_id di tombol
        // "+ Add Photo".
        $album = !empty($albumId)
            ? UniversityAlbum::where('id', $albumId)->first()
            : null;

        return view('quiz.university-album-photo.index', compact('data', 'album', 'albumId'));
    }

    // public function create()
    // {
    //     $albums = UniversityAlbum::orderBy('name')->get();

    //     return view('quiz.university-album-photo.create', compact('albums'));
    // }
    public function create(Request $request)
    {
        $albums = UniversityAlbum::orderBy('name')->get();
        $selectedAlbumId = $request->query('album_id');
    
        return view('quiz.university-album-photo.create', compact('albums', 'selectedAlbumId'));
    }

    /**
     * Simpan banyak foto sekaligus untuk 1 album (hasil dari "add row" di create).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'album_id' => 'required|exists:university_albums,id',
            'photos' => 'required|array|min:1',
            'photos.*.photo' => 'required|image|max:5120',
            'photos.*.title' => 'nullable|string|max:255',
            'photos.*.description' => 'nullable|string',
            'photos.*.sort_order' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        foreach ($validated['photos'] as $index => $row) {
            $photoPath = $this->storePhotoFile($row['photo']);

            UniversityAlbumPhoto::create([
                'user_id' => (string) $userId,
                'album_id' => $validated['album_id'],
                'photo' => $photoPath,
                'title' => $row['title'] ?? null,
                'description' => $row['description'] ?? null,
                'sort_order' => $row['sort_order'] ?? $index,
                'status' => 'active',
            ]);
        }

        // Kembali ke index foto yang sudah di-scope ke album itu (bukan
        // index global) — sesuai alur: add foto -> kembali ke index foto
        // dari album tersebut.
        return redirect()
            ->route('quiz.university-album-photo.index', ['album_id' => $validated['album_id']])
            ->with('success', 'Foto album berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(UniversityAlbumPhoto::class, $id, null);
        $albums = UniversityAlbum::orderBy('name')->get();

        return view('quiz.university-album-photo.edit', compact('data', 'albums'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(UniversityAlbumPhoto::class, $id, null);

        $validated = $request->validate([
            'album_id' => 'required|exists:university_albums,id',
            'photo' => 'nullable|image|max:5120',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $this->storePhotoFile($request->file('photo'));
            $this->deletePhotoFile($existing->photo);
        } else {
            unset($validated['photo']);
        }

        AdminCrud::update(UniversityAlbumPhoto::class, $id, $validated, null);

        return redirect()
            ->route('quiz.university-album-photo.index', ['album_id' => $validated['album_id']])
            ->with('success', 'Foto album berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(UniversityAlbumPhoto::class, $id, null);
        $albumId = $existing->album_id;

        $this->deletePhotoFile($existing->photo);

        AdminCrud::delete(UniversityAlbumPhoto::class, $id, null);

        return redirect()
            ->route('quiz.university-album-photo.index', ['album_id' => $albumId])
            ->with('success', 'Foto album berhasil dihapus.');
    }

    /**
     * Pindahkan file upload ke public/{uploadFolder} dan kembalikan path relatifnya
     * (contoh: "university/9f1c-xxxx.jpg") untuk disimpan di kolom `photo`.
     */
    private function storePhotoFile($file): string
    {
        $destination = public_path($this->uploadFolder);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return $this->uploadFolder . '/' . $filename;
    }

    /**
     * Hapus file foto lama dari public/{uploadFolder}, kalau ada.
     */
    private function deletePhotoFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}