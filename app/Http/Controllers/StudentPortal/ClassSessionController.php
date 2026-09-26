<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CoursePackage;
use App\Models\CoursePackagePurchase;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassSession\ClassSessionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * FASE 2 bagian 2 "Absensi" (16 September 2026) -- sisi SISWA dari alur
 * "Pengajuan Pemakaian Credit" (lihat docblock lengkap
 * App\Services\ClassSession\ClassSessionWorkflowService untuk alasan
 * kenapa siswa yang mengajukan duluan, bukan pengajar). Controller ini
 * MURNI urusan HTTP -- semua aturan transisi status & pemotongan credit ada
 * di ClassSessionWorkflowService, TIDAK diduplikasi di sini.
 */
class ClassSessionController extends Controller
{
    public function __construct(
        private readonly ClassSessionWorkflowService $workflowService = new ClassSessionWorkflowService()
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return redirect()->route('inayule.index')->with('status', 'Data student untuk akun ini tidak ditemukan.');
        }

        $sessions = ClassSession::where('student_id', $student->id)
            ->with(['teacher', 'coursePackage.courseClass'])
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('student-portal.inayule.class-sessions.index', compact('sessions'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return redirect()->route('inayule.index')->with('status', 'Data student untuk akun ini tidak ditemukan.');
        }

        $teachers = User::whereHas('roles', function ($query) {
            $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE);
        })->orderBy('name')->get();

        // Package yang ditampilkan HANYA dari riwayat pembelian student ini
        // sendiri (lihat docblock ClassSession -- course_package_id di sini
        // berarti "kelas jenis apa yang dipakai", BUKAN "credit-nya dari
        // pembelian package yang mana" -- itu urusan FIFO allocation di
        // Fase 1, terpisah total dari pilihan ini).
        $packages = CoursePackage::whereIn('id', CoursePackagePurchase::where('student_id', $student->id)
            ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
            ->distinct()
            ->pluck('course_package_id'))->orderBy('name')->get();

        return view('student-portal.inayule.class-sessions.create', compact('teachers', 'packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        if (!$student) {
            return redirect()->route('inayule.index')->with('status', 'Data student untuk akun ini tidak ditemukan.');
        }

        $validated = $request->validate([
            'teacher_user_id' => ['required', 'string', 'exists:users,id'],
            'course_package_id' => ['required', 'string', 'exists:course_packages,id'],
            // Requirement eksplisit owner: besaran credit HANYA 1 atau 1.5
            // (1 credit = 1 jam) -- kalau 1 hari belajar 2 jam, siswa
            // mengajukan 2 KALI terpisah masing-masing 1 credit, BUKAN
            // 1 pengajuan besaran 2.
            'credit_amount' => ['required', 'numeric', 'in:1,1.5'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $teacher = User::find($validated['teacher_user_id']);

        if (!$teacher || !$teacher->hasRole('teacher')) {
            return back()->withInput()->with('error', 'Pengajar yang dipilih tidak valid.');
        }

        $package = CoursePackage::findOrFail($validated['course_package_id']);

        try {
            $this->workflowService->requestUsage(
                $student,
                $teacher,
                $package,
                (float) $validated['credit_amount'],
                $student->branch,
                $validated['notes'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inayule.class-sessions.index')
            ->with('success', 'Pengajuan pemakaian credit berhasil dikirim, menunggu approval pengajar.');
    }
}
