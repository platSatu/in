<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            Deposit::class,
            (string) $userId,
            ['description', 'payment_status', 'payment_method'],
            $search,
            10
        );

        return view('deposit.index', compact('data'));
    }

    public function create()
    {
        return view('deposit.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'debit' => 'nullable|numeric|min:0',
            'kredit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'payment_status' => 'required|in:pending,success,failed,cancelled',
            'payment_method' => 'required|string|max:100',
            'payment_date' => 'nullable|date',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $debit = (float) ($validated['debit'] ?? 0);
        $kredit = (float) ($validated['kredit'] ?? 0);

        $validated['debit'] = $debit;
        $validated['kredit'] = $kredit;
        $validated['balance'] = $debit - $kredit;
        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Deposit::class, $validated);

        return redirect()
            ->route('deposit.index')
            ->with('success', 'Deposit berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Deposit::class, $id, (string) $userId);

        return view('deposit.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $deposit = AdminCrud::findOrFail(Deposit::class, $id, (string) $userId);

        $validated = $request->validate([
            'debit' => 'nullable|numeric|min:0',
            'kredit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'payment_status' => 'required|in:pending,success,failed,cancelled',
            'payment_method' => 'required|string|max:100',
            'payment_date' => 'nullable|date',
        ]);

        $debit = array_key_exists('debit', $validated) ? (float) $validated['debit'] : (float) $deposit->debit;
        $kredit = array_key_exists('kredit', $validated) ? (float) $validated['kredit'] : (float) $deposit->kredit;

        $validated['debit'] = $debit;
        $validated['kredit'] = $kredit;
        $validated['balance'] = $debit - $kredit;

        AdminCrud::update(Deposit::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('deposit.index')
            ->with('success', 'Deposit berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(Deposit::class, $id, (string) $userId);

        return redirect()
            ->route('deposit.index')
            ->with('success', 'Deposit berhasil dihapus.');
    }
}
