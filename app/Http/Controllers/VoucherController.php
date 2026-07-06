<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Voucher;
use App\Models\ApplicationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = AdminCrud::paginate(
            Voucher::class,
            null,
            ['code_vouchers', 'status'],
            $search,
            10,
            ['category']
        );

        return view('vouchers.index', compact('data'));
    }

    public function create()
    {
        $categories = ApplicationCategory::query()
            ->latest()
            ->get(['id', 'name']);

        return view('vouchers.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_category_id' => 'required|string|exists:application_categories,id',
            'code_vouchers' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,expired,used',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Voucher::class, $validated);

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(Voucher::class, $id, null, ['category']);
        $categories = ApplicationCategory::query()
            ->latest()
            ->get(['id', 'name']);

        return view('vouchers.edit', compact('data', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'application_category_id' => 'required|string|exists:application_categories,id',
            'code_vouchers' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,expired,used',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
        ]);

        AdminCrud::update(Voucher::class, $id, $validated, null);

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        AdminCrud::delete(Voucher::class, $id, null);

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }
}
