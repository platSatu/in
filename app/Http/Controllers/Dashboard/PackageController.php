<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PackageController extends Controller
{
    public function index(): View
    {
        $packages = Package::query()
            ->where('status', 'active')
            ->orderBy('price')
            ->get();

        return view('dashboard.package.index', compact('packages'));
    }

    public function checkout(string $id): View
    {
        if (!Auth::check()) {
            abort(401);
        }

        $package = Package::query()
            ->where('status', 'active')
            ->findOrFail($id);

        $userId = (string) Auth::id();

        $lastDeposit = Deposit::query()
            ->where('user_id', $userId)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->first();

        $currentBalance = (float) ($lastDeposit?->balance ?? 0);
        $canPay = $currentBalance >= (float) $package->price;

        return view('dashboard.package.checkout', compact('package', 'currentBalance', 'canPay'));
    }

    public function payWithDeposit(Request $request, string $id): RedirectResponse
    {
        if (!Auth::check()) {
            abort(401);
        }

        $package = Package::query()
            ->where('status', 'active')
            ->findOrFail($id);

        $userId = (string) Auth::id();
        $price = (float) $package->price;

        try {
            DB::transaction(function () use ($userId, $package, $price): void {
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
                $kredit = 0.0;
                $debit = $price;
                $newBalance = $lastBalance + $kredit - $debit;

                if ($newBalance < 0) {
                    throw new \RuntimeException('Saldo deposit tidak cukup untuk membeli package ini.');
                }

                $deposit = Deposit::create([
                    'user_id' => $userId,
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'balance' => $newBalance,
                    'description' => 'Pembelian package: ' . $package->name,
                    'payment_status' => 'success',
                    'payment_method' => 'deposit',
                    'payment_date' => now(),
                ]);

                $voucher = Voucher::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'application_category_id' => $package->application_category_id,
                    'code_vouchers' => 'PKG-' . strtoupper(Str::random(10)),
                    'status' => 'active',
                    'valid_from' => now()->toDateString(),
                    'valid_until' => now()->addDays((int) $package->duration_days)->toDateString(),
                ]);

                Transaction::create([
                    'transaction_code' => 'TRX-' . now()->format('YmdHisv') . '-' . strtoupper(Str::random(6)),
                    'user_id' => $userId,
                    'type' => 'debit',
                    'amount' => $debit,
                    'balance_before' => $lastBalance,
                    'balance_after' => $newBalance,
                    'description' => 'Pembelian package: ' . $package->name,
                    'reference_type' => 'package',
                    'reference_id' => (string) $package->id,
                    'status' => 'success',
                    'channel' => 'deposit',
                    'metadata' => [
                        'source' => 'dashboard.package.payWithDeposit',
                        'deposit_id' => (string) $deposit->id,
                        'voucher_id' => (string) $voucher->id,
                        'voucher_code' => $voucher->code_vouchers,
                        'package_id' => (string) $package->id,
                        'duration_days' => (int) $package->duration_days,
                    ],
                    'created_by' => $userId,
                    'transaction_date' => now(),
                ]);
            });

            return redirect()
                ->route('public.packages.index')
                ->with('success', 'Transaksi berhasil. Voucher berhasil dibuat.');
        } catch (Throwable $e) {
            return redirect()
                ->route('public.packages.checkout', $package->id)
                ->with('error', 'Transaksi gagal: ' . $e->getMessage());
        }
    }
}
