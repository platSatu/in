<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CourseCredit;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\CourseCredit\CourseCreditDebitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu Course > Student InaYule (13 November 2026, permintaan user -- "menu khusus murid InaYule, yang membeli paket
 * dan history"): murid yang pernah punya paket (beli, trial, upgrade, convert, import) dengan sisa credit, sisa saldo
 * deposit & pengajar tetap, lalu halaman detail berisi paket, riwayat credit, saldo, dan kelas.
 *
 * Baca-saja kecuali ganti pengajar tetap. Data per cabang dibatasi sama seperti Riwayat Credit Siswa
 * (ScopesToVisibleBranches); riwayat credit memakai partial yang sama (course-credit._ledger).
 */
class CourseStudentController extends Controller
{
    use ScopesToVisibleBranches;

    /** Batas "credit hampir habis" untuk filter & kartu ringkasan. */
    private const LOW_CREDIT = 2;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'teacher' => ['nullable', 'uuid'],
            'credit' => ['nullable', Rule::in(['low', 'empty'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $students = $this->students($request)
            ->with('teacher:id,name')
            ->select('students.*')
            ->addSelect(['credit_balance' => $this->latestCreditBalance()])
            ->addSelect(['last_activity' => CourseCredit::selectRaw('MAX(created_at)')->whereColumn('student_id', 'students.id')])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")->orWhere('handphone', 'like', "%{$search}%")))
            ->when($filters['teacher'] ?? null, fn (Builder $q, $teacher) => $q->where('teacher_user_id', $teacher))
            ->when(($filters['credit'] ?? null) === 'empty', fn (Builder $q) => $q->where($this->latestCreditBalance(), '<=', 0))
            ->when(($filters['credit'] ?? null) === 'low', fn (Builder $q) => $q->where($this->latestCreditBalance(), '>', 0)
                ->where($this->latestCreditBalance(), '<=', self::LOW_CREDIT))
            ->orderByDesc('last_activity')
            ->paginate(20)
            ->withQueryString();

        // Paket yang masih punya sisa credit, per murid di halaman ini (1 query pembelian + 1 query alokasi).
        $purchases = CoursePackagePurchase::whereIn('student_id', $students->pluck('id'))
            ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->with('coursePackage:id,name')
            ->get();
        $activePackages = $this->packagesWithRemaining($purchases)->groupBy('student_id');

        foreach ($students as $student) {
            $student->active_packages = $activePackages->get($student->id, collect());
            $student->deposit_balance = Deposit::currentBalanceFor($student->user_id);
        }

        // Ringkasan seluruh murid InaYule yang terlihat (tidak ikut filter tabel).
        $balances = $this->students($request)->select('students.id')
            ->addSelect(['credit_balance' => $this->latestCreditBalance()])
            ->pluck('credit_balance')->map(fn ($balance) => (float) $balance);

        return view('course.student.index', [
            'students' => $students,
            'teachers' => $this->teachers(),
            'filters' => array_merge($filters, ['search' => $search]),
            'lowCredit' => self::LOW_CREDIT,
            'summary' => [
                'students' => $balances->count(),
                'credits' => $balances->sum(),
                'low' => $balances->filter(fn ($balance) => $balance > 0 && $balance <= self::LOW_CREDIT)->count(),
                'empty' => $balances->filter(fn ($balance) => $balance <= 0)->count(),
            ],
        ]);
    }

    public function show(Request $request, string $id): View
    {
        $student = $this->students($request)->with(['teacher:id,name', 'companyBranch'])->findOrFail($id);

        $purchases = CoursePackagePurchase::where('student_id', $student->id)
            ->with('coursePackage:id,name')
            ->latest()
            ->get();
        $remaining = (new CourseCreditDebitService())->remainingByPurchase($purchases);
        foreach ($purchases as $purchase) {
            $purchase->credits_remaining = $remaining[$purchase->id] ?? 0.0;
        }

        return view('course.student.show', [
            'student' => $student,
            'teachers' => $this->teachers(),
            'purchases' => $purchases,
            'creditBalance' => CourseCredit::currentBalanceFor($student->id),
            'depositBalance' => Deposit::currentBalanceFor($student->user_id),
            'ledger' => CourseCredit::ledgerFor($student->id)->paginate(15, ['*'], 'credit_page')->withQueryString(),
            'deposits' => $student->user_id
                ? Deposit::where('user_id', $student->user_id)->orderByDesc('payment_date')->orderByDesc('created_at')
                    ->paginate(10, ['*'], 'saldo_page')->withQueryString()
                : null,
            'sessions' => ClassSession::where('student_id', $student->id)->with(['teacher:id,name', 'coursePackage:id,name'])
                ->latest('requested_at')->paginate(10, ['*'], 'kelas_page')->withQueryString(),
        ]);
    }

    public function updateTeacher(Request $request, string $id): RedirectResponse
    {
        $student = $this->students($request)->findOrFail($id);
        $data = $request->validate(
            ['teacher_user_id' => ['nullable', 'uuid', Rule::in($this->teachers()->pluck('id'))]],
            ['teacher_user_id.in' => 'Pilih pengajar dari daftar.'],
        );

        $student->forceFill(['teacher_user_id' => $data['teacher_user_id'] ?? null])->save();

        return back()->with('success', 'Pengajar tetap diperbarui.');
    }

    /** Murid yang pernah punya paket InaYule, dibatasi cabang yang boleh dilihat user. */
    private function students(Request $request): Builder
    {
        return Student::whereIn('students.id', CoursePackagePurchase::select('student_id'))
            ->tap(fn ($query) => $this->scopeToVisibleBranches($query, $request, 'students.branch_id'));
    }

    /** Subquery saldo credit terkini (baris ledger terakhir), sama dengan CourseCredit::currentBalanceFor(). */
    private function latestCreditBalance(): Builder
    {
        return CourseCredit::select('balance')->whereColumn('student_id', 'students.id')->orderByDesc('created_at')->limit(1);
    }

    /** Pengajar aktif (role teacher), untuk filter & pilihan pengajar tetap. */
    private function teachers(): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Pembelian yang masih bersisa credit: [student_id, name, remaining] per paket. */
    private function packagesWithRemaining(Collection $purchases): Collection
    {
        $remaining = (new CourseCreditDebitService())->remainingByPurchase($purchases);

        return $purchases->groupBy(fn ($purchase) => $purchase->student_id.'|'.$purchase->course_package_id)
            ->map(fn ($rows) => (object) [
                'student_id' => $rows->first()->student_id,
                'name' => $rows->first()->coursePackage->name ?? '-',
                'remaining' => $rows->sum(fn ($purchase) => $remaining[$purchase->id] ?? 0.0),
            ])
            ->filter(fn ($package) => $package->remaining > 0)
            ->values();
    }
}
