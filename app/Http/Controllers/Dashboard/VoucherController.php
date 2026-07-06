<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function redeemForm()
    {
        return view('dashboard.voucher.redeem');
    }

    public function redeem(Request $request)
    {
        $validated = $request->validate([
            'code_voucher' => 'required|string|max:255',
        ]);

        $code = strtoupper(trim($validated['code_voucher']));
        $today = Carbon::today();

        $voucher = Voucher::query()
            ->where('code_vouchers', $code)
            ->first();

        if (!$voucher) {
            return back()
                ->withInput()
                ->with('error', 'Kode voucher tidak ditemukan.');
        }

        if ($voucher->status === 'used') {
            return back()
                ->withInput()
                ->with('error', 'Voucher sudah digunakan.');
        }

        if ($voucher->status !== 'active') {
            return back()
                ->withInput()
                ->with('error', 'Voucher tidak aktif.');
        }

        if ($voucher->valid_from && $today->lt(Carbon::parse($voucher->valid_from)->startOfDay())) {
            return back()
                ->withInput()
                ->with('error', 'Voucher belum memasuki masa berlaku.');
        }

        if ($voucher->valid_until && $today->gt(Carbon::parse($voucher->valid_until)->endOfDay())) {
            return back()
                ->withInput()
                ->with('error', 'Voucher sudah melewati masa berlaku.');
        }

        $voucher->update([
            'status' => 'used',
        ]);

        return back()->with('success', 'Voucher berhasil diredeem.');
    }
}
