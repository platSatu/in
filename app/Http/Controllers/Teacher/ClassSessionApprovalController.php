<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Services\ClassSession\ClassSessionWorkflowService;
use App\Services\ClassSession\InvalidClassSessionStateException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * FASE 2 bagian 2 "Absensi" (16 September 2026) -- sisi PENGAJAR dari alur
 * "Pengajuan Pemakaian Credit". Digerbangi role 'teacher' (lihat
 * routes/web.php grup 'role:teacher') -- BUKAN sistem permission modul
 * seperti sisi admin, karena ini akun pengajar sendiri, bukan menu yang
 * di-grant admin ke staff tertentu.
 *
 * Pengajar approve PERSIS saat kelas dimulai (lihat docblock
 * App\Services\ClassSession\ClassSessionWorkflowService) -- SENGAJA belum
 * memotong credit sama sekali, cuma memindahkan ke antrian admin.
 */
class ClassSessionApprovalController extends Controller
{
    public function __construct(
        private readonly ClassSessionWorkflowService $workflowService = new ClassSessionWorkflowService()
    ) {
    }

    public function index(Request $request): View
    {
        $teacher = $request->user();

        $pending = ClassSession::where('teacher_user_id', $teacher->id)
            ->where('status', ClassSession::STATUS_WAITING_TEACHER)
            ->with(['student', 'coursePackage'])
            ->orderBy('requested_at')
            ->get();

        $history = ClassSession::where('teacher_user_id', $teacher->id)
            ->whereIn('status', [ClassSession::STATUS_WAITING_ADMIN, ClassSession::STATUS_APPROVED, ClassSession::STATUS_REJECTED_BY_ADMIN, ClassSession::STATUS_REJECTED_BY_TEACHER])
            ->with(['student', 'coursePackage'])
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('teacher.class-sessions.index', compact('pending', 'history'));
    }

    public function approve(Request $request, string $id): RedirectResponse
    {
        $session = ClassSession::findOrFail($id);

        try {
            $this->workflowService->teacherApprove($session, $request->user());
        } catch (InvalidClassSessionStateException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan disetujui, diteruskan ke admin untuk approval final.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $session = ClassSession::findOrFail($id);

        try {
            $this->workflowService->teacherReject($session, $request->user(), $validated['reason'] ?? null);
        } catch (InvalidClassSessionStateException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
