<?php

namespace App\Http\Controllers\Company;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyBranchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            CompanyBranch::class,
            (string) $userId,
            ['name', 'address', 'email', 'handphone'],
            $search,
            10,
            ['companyProfile']
        );

        return view('company.branch.index', compact('data'));
    }

    public function create(Request $request)
    {
        $companyProfiles = CompanyProfile::select('id', 'name')->orderBy('name')->get();
        $selectedCompanyProfileId = $request->query('company_profile_id');

        return view('company.branch.create', compact('companyProfiles', 'selectedCompanyProfileId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_profile_id' => 'required|exists:company_profiles,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
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
            $validated['logo'] = $this->storeCompanyFile($request->file('logo'), 'branch/logo');
        }

        AdminCrud::create(CompanyBranch::class, $validated);

        return redirect()
            ->route('company.branch.index')
            ->with('success', 'Company branch berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyBranch::class, $id, (string) $userId);
        $companyProfiles = CompanyProfile::select('id', 'name')->orderBy('name')->get();

        return view('company.branch.edit', compact('data', 'companyProfiles'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(CompanyBranch::class, $id, (string) $userId);

        $validated = $request->validate([
            'company_profile_id' => 'required|exists:company_profiles,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'handphone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeCompanyFile($request->file('logo'), 'branch/logo');
            $this->deleteCompanyFile($existing->logo);
        } else {
            unset($validated['logo']);
        }

        AdminCrud::update(CompanyBranch::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('company.branch.index')
            ->with('success', 'Company branch berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(CompanyBranch::class, $id, (string) $userId);

        return redirect()
            ->route('company.branch.index')
            ->with('success', 'Company branch berhasil dihapus.');
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
