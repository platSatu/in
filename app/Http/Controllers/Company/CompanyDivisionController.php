<?php

namespace App\Http\Controllers\Company;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\CompanyDivision;
use App\Models\CompanyDivisionUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

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

    public function show(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(
            CompanyDivision::class,
            $id,
            (string) $userId,
            ['companyBranch', 'divisionUsers.user']
        );

        return view('company.division.show', compact('data'));
    }

    public function addUserForm(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyDivision::class, $id, (string) $userId);

        return view('company.division.add-user', compact('data'));
    }

    public function storeUser(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyDivision::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'handphone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
        ]);

        $newUser = AdminCrud::create(User::class, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'handphone' => $validated['handphone'] ?? null,
            'status' => $validated['status'],
        ]);

        AdminCrud::create(CompanyDivisionUser::class, [
            'company_division_id' => $data->id,
            'user_id' => $newUser->id,
            'status' => 'active',
        ]);

        return redirect()
            ->route('company.division.show', $data->id)
            ->with('success', 'User baru berhasil dibuat dan ditambahkan ke divisi.');
    }

    public function removeUser(string $id, string $pivotId)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(CompanyDivision::class, $id, (string) $userId);

        $data->divisionUsers()->where('id', $pivotId)->firstOrFail()->delete();

        return redirect()
            ->route('company.division.show', $data->id)
            ->with('success', 'User berhasil dikeluarkan dari divisi.');
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
