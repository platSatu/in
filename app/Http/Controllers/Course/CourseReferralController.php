<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\ReferralRedemption;
use App\Models\ReferralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Course > Kode Referal (25 November 2026): admin mengisi 1 angka, bonus saldo (Rp) untuk pemberi kode,
 * lalu melihat siapa mengajak siapa. Aturan pemakaiannya ada di App\Services\Referral\ReferralService.
 */
class CourseReferralController extends Controller
{
    public function index(Request $request): View
    {
        $paid = ReferralRedemption::whereNotNull('commission_paid_at');

        return view('course.referral.index', [
            'setting' => ReferralSetting::current(),
            'canEdit' => $request->user()->canAccessPermission('course.referral', 'edit'),
            'redemptions' => ReferralRedemption::with(['referrer:id,name,referral_code', 'buyer:id,name,email', 'coursePackage:id,name', 'payment'])
                ->latest()
                ->paginate(20),
            'summary' => [
                'buyers' => (clone $paid)->count(),
                'commission' => (float) (clone $paid)->sum('commission_amount'),
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'referrer_bonus_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
        ], [
            'referrer_bonus_amount.required' => 'Isi besaran bonus untuk pemberi kode.',
            'referrer_bonus_amount.integer' => 'Bonus diisi angka rupiah tanpa titik atau koma, mis. 50000.',
            'referrer_bonus_amount.min' => 'Bonus tidak boleh minus.',
        ]);

        ReferralSetting::current()->update([
            'is_active' => $request->boolean('is_active'),
            'referrer_bonus_amount' => $data['referrer_bonus_amount'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', $request->boolean('is_active')
            ? 'Pengaturan kode referal disimpan dan program aktif.'
            : 'Pengaturan disimpan. Program kode referal sedang nonaktif, kolom kode referal tidak tampil di checkout.');
    }
}
