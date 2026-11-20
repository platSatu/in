<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\CoursePackage;
use App\Models\CourseVoucher;
use App\Models\CourseVoucherRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu Course > Voucher Paket (20 November 2026): voucher diskon pembelian paket credit InaYule.
 * Aturan pemakaian saat checkout ada di App\Services\CourseVoucher\CourseVoucherService.
 * Voucher yang sudah pernah dipakai tidak bisa dihapus (riwayat pemakaian dijaga), cukup dinonaktifkan.
 */
class CourseVoucherController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $vouchers = CourseVoucher::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        foreach ($vouchers as $voucher) {
            $voucher->used = $voucher->activeRedemptionCount();
        }

        return view('course.voucher.index', [
            'vouchers' => $vouchers,
            'search' => $search,
            'canEdit' => $request->user()->canAccessPermission('course.voucher', 'edit'),
        ]);
    }

    public function create(): View
    {
        return view('course.voucher.form', [
            'voucher' => new CourseVoucher([
                'discount_type' => CourseVoucher::TYPE_PERCENT,
                'only_eligible_students' => true,
                'status' => CourseVoucher::STATUS_ACTIVE,
            ]),
            'packages' => $this->packages(),
            'redemptions' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        CourseVoucher::create($data);

        return redirect()->route('course.voucher.index')->with('success', "Voucher {$data['code']} berhasil dibuat.");
    }

    public function edit(string $id): View
    {
        $voucher = CourseVoucher::findOrFail($id);

        return view('course.voucher.form', [
            'voucher' => $voucher,
            'packages' => $this->packages(),
            'used' => $voucher->activeRedemptionCount(),
            'redemptions' => $voucher->redemptions()->with(['payment', 'student', 'coursePackage:id,name'])->latest()->limit(200)->get(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $voucher = CourseVoucher::findOrFail($id);
        $data = $this->validated($request, $voucher);

        // Kode tidak boleh diganti setelah dipakai, supaya riwayat tetap jelas.
        if ($voucher->redemptions()->exists() && $data['code'] !== $voucher->code) {
            return back()->withInput()->withErrors(['code' => 'Kode voucher yang sudah pernah dipakai tidak bisa diganti. Buat voucher baru saja.']);
        }

        $voucher->update($data);

        return redirect()->route('course.voucher.index')->with('success', "Voucher {$voucher->code} berhasil diupdate.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $voucher = CourseVoucher::findOrFail($id);

        if ($voucher->redemptions()->exists()) {
            $voucher->update(['status' => CourseVoucher::STATUS_INACTIVE]);

            return back()->with('success', "Voucher {$voucher->code} sudah pernah dipakai, jadi dinonaktifkan (tidak dihapus).");
        }

        $voucher->delete();

        return back()->with('success', "Voucher {$voucher->code} dihapus.");
    }

    private function validated(Request $request, ?CourseVoucher $voucher = null): array
    {
        $request->merge(['code' => CourseVoucher::normalizeCode($request->input('code'))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('course_vouchers', 'code')->ignore($voucher?->id)],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(array_keys(CourseVoucher::TYPES))],
            'discount_value' => ['required', 'numeric', 'min:1', $request->input('discount_type') === CourseVoucher::TYPE_PERCENT ? 'max:99' : 'max:1000000000'],
            'quota' => ['required', 'integer', 'min:1', 'max:1000000'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'only_eligible_students' => ['nullable', 'boolean'],
            'course_package_ids' => ['nullable', 'array'],
            'course_package_ids.*' => ['uuid', Rule::exists('course_packages', 'id')],
            'status' => ['required', Rule::in([CourseVoucher::STATUS_ACTIVE, CourseVoucher::STATUS_INACTIVE])],
        ], [
            'code.regex' => 'Kode hanya boleh huruf, angka, strip (-) dan garis bawah (_), tanpa spasi.',
            'code.unique' => 'Kode voucher ini sudah dipakai voucher lain.',
            'discount_value.max' => 'Diskon persentase maksimal 99%.',
            'valid_until.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        // Masa berlaku dihitung per hari penuh: dari 00:00 tanggal mulai sampai 23:59 tanggal berakhir.
        $data['valid_from'] = Carbon::parse($data['valid_from'])->startOfDay();
        $data['valid_until'] = Carbon::parse($data['valid_until'])->endOfDay();
        $data['only_eligible_students'] = $request->boolean('only_eligible_students');
        $data['course_package_ids'] = array_values($data['course_package_ids'] ?? []) ?: null;

        return $data;
    }

    private function packages()
    {
        return CoursePackage::orderBy('name')->get(['id', 'name', 'price', 'status']);
    }
}
