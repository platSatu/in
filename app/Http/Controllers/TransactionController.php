<?php

namespace App\Http\Controllers;

use App\Helpers\AdminCrud;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $data = Transaction::query()
            ->with('user:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference_type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest('transaction_date')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('transaction.index', compact('data'));
    }

    public function create()
    {
        return view('transaction.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'balance_before' => 'required|numeric|min:0',
            'balance_after' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|string|max:255',
            'status' => 'required|in:pending,success,failed,cancelled',
            'transaction_date' => 'nullable|date',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(Transaction::class, $validated);

        return redirect()
            ->route('transaction.index')
            ->with('success', 'Transaction berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(Transaction::class, $id, (string) $userId);

        return view('transaction.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(Transaction::class, $id, (string) $userId);

        $validated = $request->validate([
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'balance_before' => 'required|numeric|min:0',
            'balance_after' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|string|max:255',
            'status' => 'required|in:pending,success,failed,cancelled',
            'transaction_date' => 'nullable|date',
        ]);

        AdminCrud::update(Transaction::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('transaction.index')
            ->with('success', 'Transaction berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(Transaction::class, $id, (string) $userId);

        return redirect()
            ->route('transaction.index')
            ->with('success', 'Transaction berhasil dihapus.');
    }
}
