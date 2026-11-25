<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\ReferralRedemption;
use App\Models\ReferralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Course > Kode Referal (25 November 2026): admin mengatur besaran diskon pembeli baru & komisi pemberi kode,
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
                'discount' => (float) (clone $paid)->sum('discount_amount'),
                'commission' => (float) (clone $paid)->sum('commission_amount'),
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'buyer_discount_percent' => ['required', 'numeric', 'min:0', 'max:90'],
            'referrer_commission_percent' => ['required', 'numeric', 'min:0', 'max:90'],
        ], [
            'buyer_discount_percent.max' => 'Diskon pembeli maksimal 90%.',
            'referrer_commission_percent.max' => 'Komisi pemberi kode maksimal 90%.',
        ]);

        ReferralSetting::current()->update([
            'is_active' => $request->boolean('is_active'),
            'buyer_discount_percent' => $data['buyer_discount_percent'],
            'referrer_commission_percent' => $data['referrer_commission_percent'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', $request->boolean('is_active')
            ? 'Pengaturan kode referal disimpan dan program aktif.'
            : 'Pengaturan disimpan. Program kode referal sedang nonaktif, kolom kode referal tidak tampil di checkout.');
    }
}
