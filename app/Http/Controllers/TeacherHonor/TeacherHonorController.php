<?php

namespace App\Http\Controllers\TeacherHonor;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\CourseClass;
use App\Models\Role;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Models\TeacherRate;
use App\Models\User;
use App\Services\TeacherHonor\InvalidTeacherHonorStateException;
use App\Services\TeacherHonor\TeacherHonorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        // Total jam mengajar semua pengajar per periode (25 November 2026).
        $recaps = $periods->getCollection()->mapWithKeys(fn (TeacherHonorPeriod $period) => [
            $period->id => $this->honorService->recap($period),
        ]);
        $openTotals = $periods->getCollection()
            ->filter->isOpen()
            ->mapWithKeys(fn (TeacherHonorPeriod $period) => [$period->id => $recaps[$period->id]->sum('honor_amount')]);
        $teachingHours = $recaps->map(fn ($recap) => (float) $recap->sum('teaching_hours'));

        $branches = $this->scopeToVisibleBranches(CompanyBranch::query(), $request, 'id')->orderBy('name')->get(['id', 'name']);
        $nextDates = $this->honorService->nextPeriodDates();
        // Cabang yang belum punya periode: siklus 26 -> 25 yang sedang berjalan.
        $defaultDates = $this->honorService->cycleFor(Carbon::today());

        return view('teacher-honor.index', compact('periods', 'openTotals', 'teachingHours', 'branches', 'branchId', 'nextDates', 'defaultDates'));
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

    /**
     * Rekap Gabungan (25 November 2026): honor & jam mengajar tiap pengajar
     * di semua cabang dalam 1 bulan (periode 26 -> 25, dikelompokkan menurut
     * bulan tanggal tutupnya), lalu totalnya.
     */
    public function combined(Request $request): View
    {
        $periods = TeacherHonorPeriod::query()
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->with('branch:id,name')
            ->orderByDesc('end_date')
            ->get();

        $months = $periods->groupBy(fn (TeacherHonorPeriod $period) => $period->end_date->format('Y-m'));
        $month = $request->query('month');
        $month = $months->has($month) ? $month : $months->keys()->first();
        $monthPeriods = $months->get($month, collect());

        return view('teacher-honor.combined', [
            'months' => $months->keys(),
            'month' => $month,
            'branches' => $monthPeriods->map(fn (TeacherHonorPeriod $period) => $period->branch)->filter()->unique('id')->sortBy('name')->values(),
            'rows' => $this->honorService->combinedRecap($monthPeriods),
        ]);
    }

    /** Tarif Pengajar: daftar aturan fee, diedit baris per baris (+ Tambah Baris). */
    public function rates(Request $request): View
    {
        return view('teacher-honor.rates', [
            'rows' => old('rows', $this->visibleRates($request)->map(fn (TeacherRate $rate) => [
                'teacher_user_id' => $rate->teacher_user_id,
                'branch_id' => $rate->branch_id,
                'course_class_id' => $rate->course_class_id,
                'honor_mode' => $rate->honor_mode,
                'fee' => (float) $rate->fee,
            ])->all()),
            'teachers' => User::whereHas('roles', fn ($query) => $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE))
                ->orderBy('name')->get(['id', 'name']),
            'branches' => $this->scopeToVisibleBranches(CompanyBranch::query(), $request, 'id')->orderBy('name')->get(['id', 'name']),
            'classes' => CourseClass::orderBy('name')->get(['id', 'name', 'teacher_fee', 'honor_mode']),
            // Admin cabang tidak boleh membuat aturan "semua cabang" (berlaku juga ke cabang lain).
            'canUseAllBranches' => $this->visibleBranches($request) === null,
        ]);
    }

    /** Simpan semua baris sekaligus: aturan di cakupan admin ini diganti isi form. */
    public function saveRates(Request $request): RedirectResponse
    {
        $branchIds = $this->visibleBranches($request);

        $validated = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.teacher_user_id' => ['nullable', 'string', 'exists:users,id'],
            'rows.*.branch_id' => [$branchIds === null ? 'nullable' : 'required', 'uuid', $branchIds === null ? 'exists:company_branch,id' : Rule::in($branchIds)],
            'rows.*.course_class_id' => ['nullable', 'uuid', 'exists:course_class,id'],
            'rows.*.honor_mode' => ['required', Rule::in(array_keys(CourseClass::HONOR_MODES))],
            'rows.*.fee' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ], [
            'rows.*.branch_id.required' => 'Pilih cabang di setiap baris.',
            'rows.*.fee.required' => 'Isi fee di setiap baris.',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $this->visibleRates($request)->each->delete();

            foreach ($validated['rows'] ?? [] as $row) {
                TeacherRate::create([
                    'teacher_user_id' => ($row['teacher_user_id'] ?? null) ?: null,
                    'branch_id' => ($row['branch_id'] ?? null) ?: null,
                    'course_class_id' => ($row['course_class_id'] ?? null) ?: null,
                    'honor_mode' => $row['honor_mode'],
                    'fee' => $row['fee'],
                ]);
            }
        });

        return redirect()->route('teacher-honor.rates')
            ->with('success', 'Tarif pengajar disimpan. Rekap periode yang masih berjalan langsung memakai tarif ini; periode yang sudah ditutup tidak berubah.');
    }

    /** Aturan yang boleh dilihat & diubah admin ini (admin cabang: hanya cabangnya). */
    private function visibleRates(Request $request)
    {
        return TeacherRate::query()
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->orderByRaw('teacher_user_id IS NULL, course_class_id IS NULL, branch_id IS NULL')
            ->get();
    }
}
