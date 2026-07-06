<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * GET /dashboard/tenant
     * Menampilkan daftar tenant milik user login dengan search + paginate.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = Tenant::query()
            ->where('user_id', $userId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tenant.index', compact('data'));
    }

    /**
     * GET /dashboard/tenant/create
     * Form tambah tenant.
     */
    public function create()
    {
        return view('tenant.create');
    }

    /**
     * POST /dashboard/tenant
     * Simpan tenant baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = $userId;

        Tenant::create($validated);

        return redirect()
            ->route('tenant.index')
            ->with('success', 'Tenant berhasil dibuat.');
    }

    /**
     * GET /dashboard/tenant/{id}/edit
     * Form edit tenant.
     */
    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = Tenant::where('user_id', $userId)->findOrFail($id);

        return view('tenant.edit', compact('data'));
    }

    /**
     * PUT /dashboard/tenant/{id}
     * Update tenant.
     */
    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = Tenant::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $data->update($validated);

        return redirect()
            ->route('tenant.index')
            ->with('success', 'Tenant berhasil diupdate.');
    }

    /**
     * DELETE /dashboard/tenant/{id}
     * Hapus tenant.
     */
    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = Tenant::where('user_id', $userId)->findOrFail($id);
        $data->delete();

        return redirect()
            ->route('tenant.index')
            ->with('success', 'Tenant berhasil dihapus.');
    }
}
