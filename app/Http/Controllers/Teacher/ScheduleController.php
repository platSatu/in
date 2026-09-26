<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\TeacherHonor;
use App\Models\TeacherHonorPeriod;
use App\Services\TeacherHonor\TeacherHonorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Jadwal" -- sisi PENGAJAR (17 September 2026). Sebelumnya cuma section
 * "Jadwal Hari Ini" yang nempel di halaman Approval (lihat riwayat
 * docblock lama App\Http\Controllers\Teacher\ClassSessionApprovalController),
 * sekarang dipisah jadi halaman tersendiri atas permintaan owner --
 * supaya menu "Pengajar" di sidebar jelas cuma 2 item: Approval & Jadwal.
 *
 * KEPUTUSAN SCOPE (konsisten dengan
 * App\Http\Controllers\Schedule\ScheduleAdminController): ini
 * laporan/kalender dari data ClassSession milik PENGAJAR YANG LOGIN SENDIRI
 * (beda dari versi admin yang bisa lihat & filter semua pengajar) --
 * BUKAN sistem booking slot baru. Default rentang 30 hari ke depan dari
 * hari ini, sama seperti versi admin.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfDay();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : $from->copy()->addDays(29)->endOfDay();

        $sessions = ClassSession::where('teacher_user_id', $teacher->id)
            ->whereBetween('requested_at', [$from, $to])
            ->with(['student', 'coursePackage.courseClass', 'branch'])
            ->orderBy('requested_at')
            ->get()
            ->groupBy(fn (ClassSession $session) => optional($session->requested_at)->format('Y-m-d'));

        // Honor saya: periode yang sedang berjalan (dihitung langsung) +
        // beberapa periode terakhir yang sudah ditutup.
        $honorService = new TeacherHonorService();
        $runningHonors = TeacherHonorPeriod::where('status', TeacherHonorPeriod::STATUS_OPEN)
            ->with('branch')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (TeacherHonorPeriod $period) => ['period' => $period, 'row' => $honorService->recap($period, $teacher->id)->first()])
            ->filter(fn (array $item) => $item['row'] !== null)
            ->values();
        $closedHonors = TeacherHonor::where('teacher_user_id', $teacher->id)
            ->with('period.branch')
            ->latest()
            ->limit(3)
            ->get();

        return view('teacher.schedule.index', [
            'runningHonors' => $runningHonors,
            'closedHonors' => $closedHonors,
            'sessions' => $sessions,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
