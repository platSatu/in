<?php

namespace App\Http\Controllers\Pembayaran;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\PembayaranCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            PembayaranCategory::class,
            (string) $userId,
            ['name', 'description', 'status'],
            $search,
            10
        );

        return view('pembayaran.category.index', compact('data'));
    }

    public function create()
    {
        return view('pembayaran.category.create');
    }

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

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(PembayaranCategory::class, $validated);

        return redirect()
            ->route('pembayaran.category.index')
            ->with('success', 'Kategori pembayaran berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(PembayaranCategory::class, $id, (string) $userId);

        return view('pembayaran.category.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(PembayaranCategory::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        AdminCrud::update(PembayaranCategory::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('pembayaran.category.index')
            ->with('success', 'Kategori pembayaran berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(PembayaranCategory::class, $id, (string) $userId);

        return redirect()
            ->route('pembayaran.category.index')
            ->with('success', 'Kategori pembayaran berhasil dihapus.');
    }
}
