<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function create(): View
    {
        if (!Auth::check()) {
            abort(401);
        }

        return view('dashboard.deposit.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            abort(401);
        }

        $validated = $request->validate([
            'debit' => 'required|numeric|min:10000|max:10000000',
            'description' => 'nullable|string',
        ]);

        $userId = (string) Auth::id();
        $debit = (float) $validated['debit'];

        DB::transaction(function () use ($userId, $debit, $validated): void {
            DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            $lastDeposit = Deposit::query()
                ->where('user_id', $userId)
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $lastBalance = (float) ($lastDeposit?->balance ?? 0);
            $kredit = $debit;
            $debitOut = 0.0;
            $newBalance = $lastBalance + $kredit - $debitOut;

            $deposit = Deposit::create([
                'user_id' => $userId,
                'debit' => $debitOut,
                'kredit' => $kredit,
                'balance' => $newBalance,
                'description' => $validated['description'] ?? 'Topup deposit user',
                'payment_status' => 'success',
                'payment_method' => 'deposit',
                'payment_date' => now(),
            ]);

            Transaction::create([
                'transaction_code' => 'TRX-' . now()->format('YmdHisv') . '-' . strtoupper(Str::random(6)),
                'user_id' => $userId,
                'type' => 'credit',
                'amount' => $kredit,
                'balance_before' => $lastBalance,
                'balance_after' => $newBalance,
                'description' => $validated['description'] ?? 'Topup deposit user',
                'reference_type' => 'deposit',
                'reference_id' => (string) $deposit->id,
                'status' => 'success',
                'channel' => 'deposit',
                'metadata' => [
                    'payment_method' => 'deposit',
                    'payment_status' => 'success',
                    'source' => 'dashboard.deposit.store',
                ],
                'created_by' => $userId,
                'transaction_date' => now(),
            ]);
        });

        return redirect()
            ->route('public.packages.index')
            ->with('success', 'Deposit berhasil ditambahkan.');
    }
}
