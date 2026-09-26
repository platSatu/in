<?php

namespace App\Http\Controllers\ClassSession;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Services\ClassSession\ClassSessionWorkflowService;
use App\Services\ClassSession\InvalidClassSessionStateException;
use App\Services\CourseCredit\InsufficientCourseCreditException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
 */
class ClassSessionAdminController extends Controller
{
    public function __construct(
        private readonly ClassSessionWorkflowService $workflowService = new ClassSessionWorkflowService()
    ) {
    }

    public function index(Request $request): View
    {
        $pending = ClassSession::where('status', ClassSession::STATUS_WAITING_ADMIN)
            ->with(['student', 'teacher', 'coursePackage'])
            ->orderBy('requested_at')
            ->get();

        $history = ClassSession::whereIn('status', [ClassSession::STATUS_APPROVED, ClassSession::STATUS_REJECTED_BY_ADMIN])
            ->with(['student', 'teacher', 'coursePackage'])
            ->orderByDesc('requested_at')
            ->paginate(20);

        return view('class-session.index', compact('pending', 'history'));
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

        $session = ClassSession::findOrFail($id);

        try {
            $this->workflowService->adminApprove(
                $session,
                $request->user(),
                isset($validated['corrected_credit_amount']) ? (float) $validated['corrected_credit_amount'] : null,
                $validated['notes'] ?? null
            );
        } catch (InsufficientCourseCreditException $e) {
            return back()->with('error', 'Saldo credit student tidak cukup: ' . $e->getMessage());
        } catch (InvalidClassSessionStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan disetujui dan credit sudah terpotong. Honor pengajar otomatis masuk ke rekap periodenya.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $session = ClassSession::findOrFail($id);

        try {
            $this->workflowService->adminReject($session, $request->user(), $validated['reason'] ?? null);
        } catch (InvalidClassSessionStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
