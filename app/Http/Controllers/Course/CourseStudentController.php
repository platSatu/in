<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Concerns\ScopesToVisibleBranches;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CompanyBranch;
use App\Models\CourseCredit;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Student;
use App\Services\CourseCredit\CourseCreditDebitService;
use App\Services\InaYule\StudentImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Menu Course > Student InaYule (13 November 2026, permintaan user -- "menu khusus murid InaYule, yang membeli paket
 * dan history"): murid yang pernah punya paket (beli, trial, upgrade, convert, import) dengan sisa credit, sisa saldo
 * deposit & pengajar tetap, lalu halaman detail berisi paket, riwayat credit, saldo, dan kelas.
 *
 * Baca-saja kecuali ganti pengajar tetap. Data per cabang dibatasi sama seperti Riwayat Credit Siswa
 * (ScopesToVisibleBranches); riwayat credit memakai partial yang sama (course-credit._ledger).
 * Import murid dari sistem lama: lihat App\Services\InaYule\StudentImportService (pratinjau dulu, baru disimpan).
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
            'canEdit' => $request->user()->canAccessPermission('course.student', 'edit'),
            'branches' => $this->branches($request),
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

    /** Contoh Excel import (berisi daftar paket & pengajar dari sistem). */
    public function importTemplate(StudentImportService $import): StreamedResponse
    {
        return $import->template();
    }

    /** Langkah 1 import: baca Excel & tampilkan pratinjau per baris (belum ada data yang disimpan). */
    public function importPreview(Request $request, StudentImportService $import): View|RedirectResponse
    {
        $branches = $this->branches($request);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            // Admin cabang wajib memilih cabangnya supaya murid baru terlihat di daftarnya.
            'branch_id' => [$this->visibleBranches($request) === null ? 'nullable' : 'required', Rule::in($branches->pluck('id'))],
        ], ['file.mimes' => 'File harus Excel (.xlsx/.xls).', 'file.max' => 'Ukuran file maksimal 5 MB.', 'branch_id.required' => 'Pilih cabang murid.']);

        try {
            $rows = $import->read($request->file('file'));
        } catch (Throwable) {
            $rows = false;
        }
        if (! $rows) {
            return back()->with('error', $rows === null
                ? 'Kolom nama_murid & email tidak ditemukan di baris pertama. Pakai file contoh supaya pas.'
                : 'File tidak bisa dibaca atau masih kosong. Pakai file contoh supaya pas.');
        }

        $request->session()->put('inayule_import', ['rows' => $rows, 'branch_id' => $data['branch_id'] ?? null]);
        $plan = $import->plan($rows);

        return view('course.student.import', [
            'plan' => $plan,
            'branch' => $branches->firstWhere('id', $data['branch_id'] ?? null),
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    /** Langkah 2 import: simpan murid yang lolos pengecekan (diperiksa ulang saat disimpan). */
    public function importStore(Request $request, StudentImportService $import): RedirectResponse
    {
        $pending = $request->session()->pull('inayule_import');
        if (! $pending) {
            return redirect()->route('course.student.index')->with('error', 'Data import sudah kedaluwarsa. Upload ulang file Excel-nya ya.');
        }

        $students = $import->plan($pending['rows'])['students'];
        $result = $import->import($students, $pending['branch_id'], $request->user()->id);

        $message = "Import selesai: {$result['created']} akun baru, {$result['linked']} murid lama, {$result['packages']} paket, {$result['saldo']} saldo awal.";

        return redirect()->route('course.student.index')
            ->with('success', $message)
            ->with('import_failed', $result['failed']);
    }

    /** Cabang yang boleh dipakai user ini (semua untuk role tingkat company). */
    private function branches(Request $request): Collection
    {
        return CompanyBranch::query()
            ->when($this->visibleBranches($request) !== null, fn ($q) => $q->whereIn('id', $this->visibleBranches($request)))
            ->orderBy('name')
            ->get(['id', 'name']);
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
        return app(StudentImportService::class)->teachers();
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
