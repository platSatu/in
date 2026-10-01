<?php

namespace App\Http\Controllers\ClassSession;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePurchase;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\CourseCredit\CourseCreditDebitService;
use App\Services\ClassSession\ClassSessionWorkflowService;
use App\Services\ClassSession\InvalidClassSessionStateException;
use App\Services\CourseCredit\InsufficientCourseCreditException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * FASE 2 bagian 2 "Absensi" (16 September 2026) -- sisi ADMIN dari alur
 * "Pengajuan Pemakaian Credit": gerbang TERAKHIR sebelum credit
 * benar-benar terpotong (lihat docblock
 * App\Services\ClassSession\ClassSessionWorkflowService::adminApprove()),
 * dengan kemampuan MENGOREKSI besaran credit yang diajukan siswa
 * (requirement eksplisit owner).
 *
 * Digerbangi permission modul 'class-session' (lihat config/menu.php &
 * routes/web.php), pola sama dengan modul admin lain -- BUKAN role
 * 'teacher' (itu di App\Http\Controllers\Teacher\ClassSessionApprovalController).
 *
 * Per cabang (1 Oktober 2026): admin hanya melihat & memproses sesi/siswa di
 * cabang yang jadi cakupan role-nya (ScopesToVisibleBranches).
 */
class ClassSessionAdminController extends Controller
{
    use ScopesToVisibleBranches;

    public function __construct(
        private readonly ClassSessionWorkflowService $workflowService = new ClassSessionWorkflowService()
    ) {
    }

    public function index(Request $request): View
    {
        $pending = ClassSession::where('status', ClassSession::STATUS_WAITING_ADMIN)
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->with(['student', 'teacher', 'coursePackage'])
            ->orderBy('requested_at')
            ->get();

        // Belum disetujui pengajar -- admin hanya bisa menolak (mis. pengajar
        // tidak merespons), supaya periode Honor Pengajar bisa ditutup.
        $waitingTeacher = ClassSession::where('status', ClassSession::STATUS_WAITING_TEACHER)
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->with(['student', 'teacher', 'coursePackage'])
            ->orderBy('requested_at')
            ->get();

        $history = ClassSession::whereIn('status', [ClassSession::STATUS_APPROVED, ClassSession::STATUS_REJECTED_BY_ADMIN, ClassSession::STATUS_REFUNDED])
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->with(['student', 'teacher', 'coursePackage', 'refundedBy'])
            ->orderByDesc('requested_at')
            ->paginate(20);

        return view('class-session.index', [
            'pending' => $pending,
            'waitingTeacher' => $waitingTeacher,
            'history' => $history,
            'chargeOptions' => $this->chargeOptions($request),
            'teachers' => User::whereHas('roles', fn ($query) => $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Riwayat mutasi credit per siswa (hanya baca). */
    public function creditHistory(Request $request): View
    {
        $students = Student::whereIn('id', CoursePackagePurchase::select('student_id'))
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $studentId = $request->query('student');
        $student = is_string($studentId) ? $students->firstWhere('id', $studentId) : null;
        $packages = [];

        if ($student) {
            $purchases = CoursePackagePurchase::where('student_id', $student->id)
                ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
                ->with('coursePackage:id,name')
                ->get();
            $remaining = (new CourseCreditDebitService())->remainingByPurchase($purchases);
            $packages = $purchases->groupBy('course_package_id')->map(fn ($rows) => [
                'name' => $rows->first()->coursePackage->name ?? '-',
                'remaining' => $rows->sum(fn ($purchase) => $remaining[$purchase->id] ?? 0.0),
            ])->values()->all();
        }

        return view('class-session.credit-history', [
            'students' => $students,
            'student' => $student,
            'packages' => $packages,
            'ledger' => $student ? CourseCredit::ledgerFor($student->id)->paginate(20)->withQueryString() : null,
        ]);
    }

    /** Refund credit sesi yang sudah disetujui (credit kembali, honor batal). */
    public function refund(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Tulis alasan refund.']);

        try {
            $this->workflowService->refund($this->visibleSession($request, $id), $request->user(), $validated['reason']);
        } catch (InvalidClassSessionStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credit berhasil dikembalikan ke paket siswa. Sesi ini tidak lagi dihitung honor.');
    }

    /** Potong credit manual oleh admin (mis. siswa tidak hadir, diganti video). */
    public function charge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_package' => ['required', 'string', 'regex:/^[0-9a-f-]{36}\|[0-9a-f-]{36}$/i'],
            'teacher_user_id' => ['nullable', 'string', 'exists:users,id'],
            'credit_amount' => ['required', 'numeric', 'min:0.5', 'max:10', 'multiple_of:0.5'],
            'class_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'student_package.required' => 'Pilih siswa & paket.',
            'class_at.before_or_equal' => 'Tanggal kelas tidak boleh di masa depan.',
            'reason.required' => 'Tulis alasannya, mis. "Tidak hadir, diganti video".',
        ]);

        [$studentId, $packageId] = explode('|', $validated['student_package']);
        $student = Student::findOrFail($studentId);
        $this->abortUnlessBranchVisible($request, $student->branch_id);

        try {
            $this->workflowService->adminCharge(
                $student,
                CoursePackage::findOrFail($packageId),
                isset($validated['teacher_user_id']) ? User::find($validated['teacher_user_id']) : null,
                (float) $validated['credit_amount'],
                Carbon::parse($validated['class_at']),
                $validated['reason'],
                $request->user()
            );
        } catch (InsufficientCourseCreditException $e) {
            return back()->withInput()->with('error', 'Sisa credit paket ini tidak cukup: ' . $e->getMessage());
        } catch (InvalidClassSessionStateException|InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credit berhasil dipotong dan tercatat sebagai kelas yang dihadiri.');
    }

    /**
     * Pilihan "siswa -- paket" yang masih punya sisa credit, untuk form potong manual.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function chargeOptions(Request $request): array
    {
        $purchases = CoursePackagePurchase::where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->whereIn('student_id', $this->scopeToVisibleBranches(Student::select('id'), $request))
            ->with(['student:id,first_name,last_name', 'coursePackage:id,name'])
            ->get();
        $remaining = (new CourseCreditDebitService())->remainingByPurchase($purchases);

        return $purchases->groupBy(fn ($purchase) => $purchase->student_id . '|' . $purchase->course_package_id)
            ->map(function ($rows, $key) use ($remaining) {
                $sisa = $rows->sum(fn ($purchase) => $remaining[$purchase->id] ?? 0.0);
                $first = $rows->first();

                return $sisa > 0 ? [
                    'value' => $key,
                    'label' => trim(($first->student->first_name ?? '') . ' ' . ($first->student->last_name ?? '')) . ' -- ' . ($first->coursePackage->name ?? '-') . ' (sisa ' . rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') . ' credit)',
                ] : null;
            })
            ->filter()
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * $correctedAmount diisi kalau admin mengoreksi besaran yang diajukan
     * siswa -- lihat docblock ClassSessionWorkflowService::adminApprove().
     */
    public function approve(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'corrected_credit_amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = $this->visibleSession($request, $id);

        try {
            $this->workflowService->adminApprove(
                $session,
                $request->user(),
                isset($validated['corrected_credit_amount']) ? (float) $validated['corrected_credit_amount'] : null,
                $validated['notes'] ?? null
            );
        } catch (InsufficientCourseCreditException $e) {
            return back()->with('error', 'Saldo credit student tidak cukup: ' . $e->getMessage());
        } catch (InvalidClassSessionStateException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan disetujui dan credit sudah terpotong. Honor pengajar otomatis masuk ke rekap periodenya.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $session = $this->visibleSession($request, $id);

        try {
            $this->workflowService->adminReject($session, $request->user(), $validated['reason'] ?? null);
        } catch (InvalidClassSessionStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan ditolak.');
    }

    /** Sesi di cabang yang boleh dilihat admin ini; cabang lain = 404. */
    private function visibleSession(Request $request, string $id): ClassSession
    {
        $session = ClassSession::findOrFail($id);
        $this->abortUnlessBranchVisible($request, $session->branch_id);

        return $session;
    }
}
