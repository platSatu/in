<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\City;
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

    public function create()
    {
        $cities = City::select('id', 'name')->orderBy('name')->get();

        return view('quiz.university.create', compact('cities'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'city' => 'required|exists:cities,id',
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
    
            if ($request->hasFile('logo')) {
                $validated['logo'] = $this->storeUniversityFile($request->file('logo'), 'logo');
            }
    
            if ($request->hasFile('banner')) {
                $validated['banner'] = $this->storeUniversityFile($request->file('banner'), 'banner');
            }
    
            if ($request->hasFile('attachment')) {
                $validated['attachment'] = $this->storeUniversityFile($request->file('attachment'), 'attachment');
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
                ->with('error', 'University gagal dibuat. Silakan cek log untuk detail error.');
        }
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'name' => 'required|string|max:255',
    //             'country' => 'required|string|max:255',
    //             'city' => 'required|exists:cities,id',
    //             'description' => 'nullable|string',
    //             'status' => 'nullable|string',
    //             'logo' => 'nullable|image|max:2048',
    //             'banner' => 'nullable|image|max:4096',
    //             'attachment' => 'nullable|mimes:jpg,jpeg,pdf|max:5120',
    //         ]);

    //         $userId = Auth::id();

    //         if ($userId === null) {
    //             abort(401);
    //         }

    //         $validated['user_id'] = (string) $userId;

    //         if ($request->hasFile('logo')) {
    //             $validated['logo'] = $this->storeUniversityFile($request->file('logo'), 'logo');
    //         }

    //         if ($request->hasFile('banner')) {
    //             $validated['banner'] = $this->storeUniversityFile($request->file('banner'), 'banner');
    //         }

    //         if ($request->hasFile('attachment')) {
    //             $validated['attachment'] = $this->storeUniversityFile($request->file('attachment'), 'attachment');
    //         }

    //         \Log::info('Create University Data:', $validated);

    //         AdminCrud::create(University::class, $validated);

    //         return redirect()
    //             ->route('quiz.university.index')
    //             ->with('success', 'University berhasil dibuat.');

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         \Log::error('Validation Error Create University:', [
    //             'errors' => $e->errors(),
    //             'request' => $request->all(),
    //         ]);

    //         throw $e;

    //     } catch (\Exception $e) {
    //         \Log::error('Failed Create University:', [
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //             'request' => $request->all(),
    //         ]);

    //         return redirect()
    //             ->back()
    //             ->withInput()
    //             ->with('error', 'University gagal dibuat. Silakan cek log untuk detail error.');
    //     }
    // }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'country' => 'required|string|max:255',
    //         'city' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'status' => 'nullable|string',
    //         'logo' => 'nullable|image|max:2048',
    //         'banner' => 'nullable|image|max:4096',
    //         'attachment' => 'nullable|mimes:jpg,jpeg,pdf|max:5120',
    //     ]);

    //     $userId = Auth::id();
    //     if ($userId === null) {
    //         abort(401);
    //     }

    //     $validated['user_id'] = (string) $userId;

    //     if ($request->hasFile('logo')) {
    //         $validated['logo'] = $this->storeUniversityFile($request->file('logo'), 'logo');
    //     } else {
    //         unset($validated['logo']);
    //     }

    //     if ($request->hasFile('banner')) {
    //         $validated['banner'] = $this->storeUniversityFile($request->file('banner'), 'banner');
    //     } else {
    //         unset($validated['banner']);
    //     }

    //     if ($request->hasFile('attachment')) {
    //         $validated['attachment'] = $this->storeUniversityFile($request->file('attachment'), 'attachment');
    //     } else {
    //         unset($validated['attachment']);
    //     }

    //     AdminCrud::create(University::class, $validated);

    //     return redirect()
    //         ->route('quiz.university.index')
    //         ->with('success', 'University berhasil dibuat.');
    // }
    

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

        return view('quiz.university.edit', compact('data', 'cities'));
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
            // 'city' => 'required|string|max:255',
            'city' => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
            'attachment' => 'nullable|mimes:jpg,jpeg,pdf|max:5120',
        ]);

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
