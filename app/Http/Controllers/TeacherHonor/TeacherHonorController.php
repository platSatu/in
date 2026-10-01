<?php

namespace App\Http\Controllers\TeacherHonor;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Services\TeacherHonor\InvalidTeacherHonorStateException;
use App\Services\TeacherHonor\TeacherHonorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Menu Honor Pengajar: periode per cabang -> rekap per pengajar (jumlah
 * kelas x fee Course Class) -> tutup periode -> setujui -> tandai dibayar.
 * Aturan hitungnya ada di TeacherHonorService. Sistem ini TIDAK mentransfer
 * uang, hanya mencatat statusnya. Digerbangi permission 'teacher-honor'.
 * Per cabang: admin hanya melihat & memproses periode cabangnya sendiri
 * (ScopesToVisibleBranches).
 */
class TeacherHonorController extends Controller
{
    use ScopesToVisibleBranches;

    public function __construct(
        private readonly TeacherHonorService $honorService = new TeacherHonorService()
    ) {
    }

    public function index(Request $request): View
    {
        $branchId = $request->query('branch_id');

        $periods = TeacherHonorPeriod::query()
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
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

        $branches = $this->scopeToVisibleBranches(CompanyBranch::query(), $request, 'id')->orderBy('name')->get(['id', 'name']);
        $nextDates = $this->honorService->nextPeriodDates();

        return view('teacher-honor.index', compact('periods', 'openTotals', 'branches', 'branchId', 'nextDates'));
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

        $this->abortUnlessBranchVisible($request, $validated['branch_id']);

        $invalid = $this->honorService->validateNewPeriod(
            $validated['branch_id'],
            Carbon::parse($validated['start_date']),
            Carbon::parse($validated['end_date'])
        );

        if ($invalid) {
            return back()->withInput()->with('error', $invalid);
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
        $period = $this->visiblePeriod($request, $id)->load('branch');
        $recap = $this->honorService->recap($period);
        $closeBlockedReason = $period->isOpen() ? $this->honorService->closeBlockedReason($period) : null;

        return view('teacher-honor.show', compact('period', 'recap', 'closeBlockedReason'));
    }

    public function close(Request $request, string $id): RedirectResponse
    {
        $period = $this->visiblePeriod($request, $id);

        try {
            $this->honorService->close($period, $request->user());
        } catch (InvalidTeacherHonorStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Periode ditutup dan rekapnya sudah dikunci. Sekarang honor tiap pengajar bisa disetujui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $period = $this->visiblePeriod($request, $id);

        if (! $period->isOpen()) {
            return back()->with('error', 'Periode yang sudah ditutup tidak bisa dihapus.');
        }

        // Menghapus periode di tengah akan menyisakan tanggal bolong.
        $hasLaterPeriod = TeacherHonorPeriod::where('branch_id', $period->branch_id)
            ->where('start_date', '>', $period->end_date)
            ->exists();

        if ($hasLaterPeriod) {
            return back()->with('error', 'Hanya periode terakhir di cabang ini yang bisa dihapus, supaya tidak ada tanggal yang terlewat.');
        }

        $period->delete();

        return redirect()->route('teacher-honor.index')->with('success', 'Periode berhasil dihapus.');
    }

    public function approvePayout(Request $request, string $id): RedirectResponse
    {
        return $this->transition(fn () => $this->honorService->approveForPayout($this->visibleHonor($request, $id), $request->user()),
            'Honor disetujui. Tinggal ditandai setelah dibayar ya.');
    }

    public function markPaid(Request $request, string $id): RedirectResponse
    {
        return $this->transition(fn () => $this->honorService->markPaid($this->visibleHonor($request, $id)),
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

    /** Periode di cabang yang boleh dilihat admin ini; cabang lain = 404. */
    private function visiblePeriod(Request $request, string $id): TeacherHonorPeriod
    {
        $period = TeacherHonorPeriod::findOrFail($id);
        $this->abortUnlessBranchVisible($request, $period->branch_id);

        return $period;
    }

    private function visibleHonor(Request $request, string $id): TeacherHonor
    {
        $honor = TeacherHonor::with('period')->findOrFail($id);
        $this->abortUnlessBranchVisible($request, $honor->period?->branch_id);

        return $honor;
    }
}
