<?php

namespace App\Http\Controllers\Company;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyProfileController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            CompanyProfile::class,
            (string) $userId,
            ['name', 'address', 'email', 'handphone'],
            $search,
            10
        );

        return view('company.profile.index', compact('data'));
    }

    public function create()
    {
        return view('company.profile.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'required|image|max:2048',
            'address' => 'nullable|string',
            'handphone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeCompanyFile($request->file('logo'), 'profile/logo');
        }

        AdminCrud::create(CompanyProfile::class, $validated);

        return redirect()
            ->route('company.profile.index')
            ->with('success', 'Company profile berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyProfile::class, $id, (string) $userId);

        return view('company.profile.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(CompanyProfile::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'handphone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeCompanyFile($request->file('logo'), 'profile/logo');
            $this->deleteCompanyFile($existing->logo);
        } else {
            unset($validated['logo']);
        }

        AdminCrud::update(CompanyProfile::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('company.profile.index')
            ->with('success', 'Company profile berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(CompanyProfile::class, $id, (string) $userId);

        return redirect()
            ->route('company.profile.index')
            ->with('success', 'Company profile berhasil dihapus.');
    }

    /**
     * Simpan file logo ke public/company/{folder}, kembalikan path relatifnya.
     */
    private function storeCompanyFile($file, string $folder): string
    {
        $destination = public_path('company/' . $folder);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'company/' . $folder . '/' . $filename;
    }

    /**
     * Hapus file logo lama dari public/company/{folder}, kalau ada.
     */
    private function deleteCompanyFile(?string $relativePath): void
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
