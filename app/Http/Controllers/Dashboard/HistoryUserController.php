<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistoryUserController extends Controller
{
    public function index(): View
    {
        if (!Auth::check()) {
            abort(401);
        }

        $userId = (string) Auth::id();

        $deposits = Deposit::query()
            ->where('user_id', $userId)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();

        $vouchers = Voucher::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.history-user.index', compact('deposits', 'vouchers'));
    }
}
