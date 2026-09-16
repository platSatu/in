<?php

namespace App\Http\Controllers\TeacherHonor;

use App\Http\Controllers\Controller;
use App\Models\TeacherHonor;
use App\Services\TeacherHonor\InvalidTeacherHonorStateException;
use App\Services\TeacherHonor\TeacherHonorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FASE 3 "Perhitungan Honor Pengajar" (16 September 2026) -- laporan &
 * approval payout honor pengajar. Baris TeacherHonor sendiri SUDAH tercatat
 * otomatis lewat App\Services\TeacherHonor\TeacherHonorService::recordForSession()
 * (dipanggil ClassSessionWorkflowService::adminApprove(), lihat docblocknya)
 * -- controller ini HANYA menampilkan & menjalankan 2 transisi status
 * berikutnya:
 *
 *   pending -> approved_for_payout (approvePayout, oleh Manager)
 *   approved_for_payout -> paid (markPaid, setelah honor benar-benar
 *   ditransfer di luar sistem -- SISTEM INI TIDAK melakukan transfer uang
 *   apa pun, cuma mencatat statusnya)
 *
 * Digerbangi permission modul 'teacher-honor' (lihat config/menu.php).
 */
class TeacherHonorController extends Controller
{
    public function __construct(
        private readonly TeacherHonorService $honorService = new TeacherHonorService()
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $honors = TeacherHonor::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['teacher', 'student', 'classSession.coursePackage', 'branch'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending' => TeacherHonor::where('status', TeacherHonor::STATUS_PENDING)->sum('honor_amount'),
            'approved_for_payout' => TeacherHonor::where('status', TeacherHonor::STATUS_APPROVED_FOR_PAYOUT)->sum('honor_amount'),
            'paid' => TeacherHonor::where('status', TeacherHonor::STATUS_PAID)->sum('honor_amount'),
        ];

        return view('teacher-honor.index', compact('honors', 'summary', 'status'));
    }

    /**
     * Approval FINANSIAL oleh Manager -- BUKAN approval per ClassSession
     * (itu sudah selesai di admin, Fase 2). Lihat docblock
     * TeacherHonorService::approveForPayout().
     */
    public function approvePayout(Request $request, string $id): RedirectResponse
    {
        $honor = TeacherHonor::findOrFail($id);

        try {
            $this->honorService->approveForPayout($honor, $request->user());
        } catch (InvalidTeacherHonorStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Honor disetujui untuk payout.');
    }

    public function markPaid(Request $request, string $id): RedirectResponse
    {
        $honor = TeacherHonor::findOrFail($id);

        try {
            $this->honorService->markPaid($honor);
        } catch (InvalidTeacherHonorStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Honor ditandai sudah dibayar.');
    }
}
