<?php

namespace App\Http\Controllers\TeacherHonor;

use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Services\TeacherHonor\InvalidTeacherHonorStateException;
use App\Services\TeacherHonor\TeacherHonorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Honor Pengajar: periode per cabang -> rekap per pengajar (jumlah
 * kelas x fee Course Class) -> tutup periode -> setujui -> tandai dibayar.
 * Aturan hitungnya ada di TeacherHonorService. Sistem ini TIDAK mentransfer
 * uang, hanya mencatat statusnya. Digerbangi permission 'teacher-honor'.
 */
class TeacherHonorController extends Controller
{
    public function __construct(
        private readonly TeacherHonorService $honorService = new TeacherHonorService()
    ) {
    }

    public function index(Request $request): View
    {
        $branchId = $request->query('branch_id');

        $periods = TeacherHonorPeriod::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with('branch')
            ->withSum('honors', 'honor_amount')
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        // Periode terbuka dihitung langsung supaya angka di daftar sama
        // dengan halaman detailnya.
        $openTotals = $periods->getCollection()
            ->filter->isOpen()
            ->mapWithKeys(fn (TeacherHonorPeriod $period) => [
                $period->id => $this->honorService->recap($period)->sum('honor_amount'),
            ]);

        $branches = CompanyBranch::orderBy('name')->get(['id', 'name']);

        return view('teacher-honor.index', compact('periods', 'openTotals', 'branches', 'branchId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'uuid', 'exists:company_branch,id'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Tanggal tutup tidak boleh sebelum tanggal mulai.',
        ]);

        $overlaps = TeacherHonorPeriod::where('branch_id', $validated['branch_id'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->with('error', 'Tanggalnya bentrok dengan periode lain di cabang yang sama. Coba pilih rentang tanggal lain ya.');
        }

        $period = TeacherHonorPeriod::create($validated + [
            'status' => TeacherHonorPeriod::STATUS_OPEN,
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('teacher-honor.show', $period->id)
            ->with('success', 'Periode berhasil dibuat. Honor pengajar akan terhitung otomatis dari sesi yang disetujui.');
    }

    public function show(Request $request, string $id): View
    {
        $period = TeacherHonorPeriod::with('branch')->findOrFail($id);
        $recap = $this->honorService->recap($period);

        return view('teacher-honor.show', compact('period', 'recap'));
    }

    public function close(Request $request, string $id): RedirectResponse
    {
        $period = TeacherHonorPeriod::findOrFail($id);

        try {
            $this->honorService->close($period, $request->user());
        } catch (InvalidTeacherHonorStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Periode ditutup dan rekapnya sudah dikunci. Sekarang honor tiap pengajar bisa disetujui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $period = TeacherHonorPeriod::findOrFail($id);

        if (! $period->isOpen()) {
            return back()->with('error', 'Periode yang sudah ditutup tidak bisa dihapus.');
        }

        $period->delete();

        return redirect()->route('teacher-honor.index')->with('success', 'Periode berhasil dihapus.');
    }

    public function approvePayout(Request $request, string $id): RedirectResponse
    {
        return $this->transition(fn () => $this->honorService->approveForPayout(TeacherHonor::findOrFail($id), $request->user()),
            'Honor disetujui. Tinggal ditandai setelah dibayar ya.');
    }

    public function markPaid(string $id): RedirectResponse
    {
        return $this->transition(fn () => $this->honorService->markPaid(TeacherHonor::findOrFail($id)),
            'Honor ditandai sudah dibayar. Terima kasih!');
    }

    private function transition(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidTeacherHonorStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
