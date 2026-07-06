<?php

namespace App\Http\Controllers\Pembayaran;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\PembayaranCategory;
use App\Models\PembayaranForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranFormsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            PembayaranForm::class,
            (string) $userId,
            ['name', 'description', 'status'],
            $search,
            10,
            ['category']
        );

        return view('pembayaran.form.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $categories = PembayaranCategory::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('pembayaran.form.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pembayaran_category_id' => 'required|string|exists:pembayaran_categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $categoryOwned = PembayaranCategory::query()
            ->where('id', $validated['pembayaran_category_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$categoryOwned) {
            abort(403, 'Category pembayaran tidak valid untuk user ini.');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(PembayaranForm::class, $validated);

        return redirect()
            ->route('pembayaran.form.index')
            ->with('success', 'Form pembayaran berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(PembayaranForm::class, $id, (string) $userId, ['category']);

        $categories = PembayaranCategory::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('pembayaran.form.edit', compact('data', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(PembayaranForm::class, $id, (string) $userId);

        $validated = $request->validate([
            'pembayaran_category_id' => 'required|string|exists:pembayaran_categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $categoryOwned = PembayaranCategory::query()
            ->where('id', $validated['pembayaran_category_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$categoryOwned) {
            abort(403, 'Category pembayaran tidak valid untuk user ini.');
        }

        AdminCrud::update(PembayaranForm::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('pembayaran.form.index')
            ->with('success', 'Form pembayaran berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(PembayaranForm::class, $id, (string) $userId);

        return redirect()
            ->route('pembayaran.form.index')
            ->with('success', 'Form pembayaran berhasil dihapus.');
    }
}
