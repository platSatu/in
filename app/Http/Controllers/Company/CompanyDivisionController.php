<?php

namespace App\Http\Controllers\Company;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\CompanyDivision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyDivisionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            CompanyDivision::class,
            (string) $userId,
            ['name', 'description'],
            $search,
            10,
            ['companyBranch']
        );

        return view('company.division.index', compact('data'));
    }

    public function create(Request $request)
    {
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();
        $selectedCompanyBranchId = $request->query('company_branch_id');

        return view('company.division.create', compact('companyBranches', 'selectedCompanyBranchId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_branch_id' => 'required|exists:company_branch,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(CompanyDivision::class, $validated);

        return redirect()
            ->route('company.division.index')
            ->with('success', 'Company division berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyDivision::class, $id, (string) $userId);
        $companyBranches = CompanyBranch::select('id', 'name')->orderBy('name')->get();

        return view('company.division.edit', compact('data', 'companyBranches'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(CompanyDivision::class, $id, (string) $userId);

        $validated = $request->validate([
            'company_branch_id' => 'required|exists:company_branch,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        AdminCrud::update(CompanyDivision::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('company.division.index')
            ->with('success', 'Company division berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(CompanyDivision::class, $id, (string) $userId);

        return redirect()
            ->route('company.division.index')
            ->with('success', 'Company division berhasil dihapus.');
    }
}
