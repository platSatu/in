<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\City;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniversityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            University::class,
            (string) $userId,
            ['name', 'country', 'city', 'description'],
            $search,
            10
        );

        $data->load('city');

        return view('quiz.university.index', compact('data'));
    }

    /**
     * Kalau datang dari index Major lewat tombol "+ Add University" (query
     * ?major_id=...), field Major dikunci, dan City + Country ikut dikunci
     * (disabled, sama seperti City) karena keduanya bisa diturunkan dari
     * rantai Major -> City -> Country. Country hanya dikunci kalau City-nya
     * memang sudah punya Country (relasi baru, bisa saja masih kosong untuk
     * data lama) — kalau tidak, Country tetap free text seperti biasa.
     */
    public function create(Request $request)
    {
        $cities = City::select('id', 'name')->orderBy('name')->get();

        $selectedMajorId = $request->query('major_id');
        $lockedMajor = null;
        $lockedCityId = null;
        $lockedCountryName = null;

        if ($selectedMajorId) {
            $lockedMajor = Major::with('city.country')->find($selectedMajorId);
            $lockedCityId = $lockedMajor->city_id ?? null;

            if ($lockedMajor && $lockedMajor->city && $lockedMajor->city->country) {
                $lockedCountryName = $lockedMajor->city->country->name;
            }
        }

        return view('quiz.university.create', compact(
            'cities',
            'selectedMajorId',
            'lockedMajor',
            'lockedCityId',
            'lockedCountryName'
        ));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'city' => 'required|exists:cities,id',
                'major_id' => 'nullable|exists:majors,id',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
                'logo' => 'nullable|image|max:2048',
                'banner' => 'nullable|image|max:4096',
                'attachment' => 'nullable|mimes:jpg,jpeg,pdf|max:5120',
            ]);

            $userId = Auth::id();

            if ($userId === null) {
                abort(401);
            }

            $validated['user_id'] = (string) $userId;

            // Kolom `status` di tabel `universities` NOT NULL tanpa default,
            // sedangkan pilihan "Choose..." di form mengirim string kosong
            // (bisa berakhir null/'' tergantung middleware). Default-kan ke
            // 'active' kalau kosong, supaya insert tidak gagal (SQLSTATE 1364).
            if (empty($validated['status'])) {
                $validated['status'] = 'active';
            }

            if ($request->hasFile('logo')) {
                $validated['logo'] = $this->storeUniversityFile($request->file('logo'), 'logo');
            }

            if ($request->hasFile('banner')) {
                $validated['banner'] = $this->storeUniversityFile($request->file('banner'), 'banner');
            }

            if ($request->hasFile('attachment')) {
                $validated['attachment'] = $this->storeUniversityFile($request->file('attachment'), 'attachment');
            }

            // Sama seperti `status`: kolom `logo`/`banner`/`attachment` di
            // tabel `universities` NOT NULL tanpa default. Ketiganya memang
            // boleh dikosongkan di form (lihat "Boleh dikosongkan." di
            // bawah masing-masing input), jadi kalau tidak ada file yang
            // diupload, default-kan ke string kosong supaya insert tidak
            // gagal (SQLSTATE 1364) — bukan berarti file jadi wajib.
            foreach (['logo', 'banner', 'attachment'] as $fileField) {
                if (empty($validated[$fileField])) {
                    $validated[$fileField] = '';
                }
            }

            \Log::info('Create University Data:', $validated);

            $university = AdminCrud::create(University::class, $validated);

            // Lanjut ke Add University Profile, university_id sudah terkunci di form.
            if ($university && isset($university->id)) {
                return redirect()
                    ->route('quiz.university-profile.create', ['university_id' => $university->id])
                    ->with('success', 'University berhasil dibuat. Sekarang lengkapi profile-nya.');
            }

            return redirect()
                ->route('quiz.university.index')
                ->with('success', 'University berhasil dibuat.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error Create University:', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            \Log::error('Failed Create University:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'request' => $request->all(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'University gagal dibuat: ' . $e->getMessage());
        }
    }

    /**
     * Simpan file (logo/banner/attachment) ke public/university/{folder}, kembalikan path relatifnya.
     */
    private function storeUniversityFile($file, string $folder): string
    {
        $destination = public_path('university/' . $folder);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'university/' . $folder . '/' . $filename;
    }

    /**
     * Halaman detail kampus, dibuka dari index City -> Show -> klik nama
     * university. Menampilkan profile, city/country, dan seluruh foto
     * (dikumpulkan dari semua album aktif milik university ini).
     */
    public function show(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(University::class, $id, (string) $userId, ['city.country', 'major', 'profiles']);

        $data->load(['albums' => function ($query) {
            $query->where('status', 'active')->with(['photos' => function ($q) {
                $q->orderBy('sort_order');
            }]);
        }]);

        return view('quiz.university.show', compact('data'));
    }

    public function edit(string $id)
    {
        $userId = Auth::id();

        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(University::class, $id, (string) $userId);

        $cities = City::select('id', 'name')
            ->orderBy('name')
            ->get();

        $majors = Major::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('quiz.university.edit', compact('data', 'cities', 'majors'));
    }


    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(University::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|exists:cities,id',
            'major_id' => 'nullable|exists:majors,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
            'attachment' => 'nullable|mimes:jpg,jpeg,pdf|max:5120',
        ]);

        // Sama seperti store(): kolom `status` NOT NULL tanpa default. Kalau
        // form dikirim tanpa status (mis. "Choose..." kepilih tidak sengaja),
        // pertahankan status yang sudah ada, bukan di-null-kan.
        if (empty($validated['status'])) {
            $validated['status'] = $existing->status ?: 'active';
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeUniversityFile($request->file('logo'), 'logo');
            $this->deleteUniversityFile($existing->logo);
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('banner')) {
            $validated['banner'] = $this->storeUniversityFile($request->file('banner'), 'banner');
            $this->deleteUniversityFile($existing->banner);
        } else {
            unset($validated['banner']);
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $this->storeUniversityFile($request->file('attachment'), 'attachment');
            $this->deleteUniversityFile($existing->attachment);
        } else {
            unset($validated['attachment']);
        }

        AdminCrud::update(University::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('quiz.university.index')
            ->with('success', 'University berhasil diupdate.');
    }

    /**
     * Hapus file lama (logo/banner/attachment) dari public/university/{folder}, kalau ada.
     * Taruh berdampingan dengan storeUniversityFile() yang sudah ditambahkan sebelumnya.
     */
    private function deleteUniversityFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(University::class, $id, (string) $userId);

        return redirect()
            ->route('quiz.university.index')
            ->with('success', 'University berhasil dihapus.');
    }
}
