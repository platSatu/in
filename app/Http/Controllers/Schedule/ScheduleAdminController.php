<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FASE 2 bagian 3 "Jadwal" (16 September 2026) -- sisi ADMIN/MANAGER:
 * "admin lihat jadwal semua pengajar dan lihat semua studentnya" (requirement
 * eksplisit dari diskusi Jadwal).
 *
 * KEPUTUSAN SCOPE (disepakati eksplisit dengan owner sebelum dibangun): ini
 * adalah KALENDER/LAPORAN dari data App\Models\ClassSession yang SUDAH ada
 * (Fase 2 bagian 2 "Pengajuan Pemakaian Credit"), BUKAN sistem rencana ke
 * depan / booking slot sebelum kelas terjadi -- fitur "admin assign
 * pengajar ke slot kosong" & "cari slot kosong" yang sempat didiskusikan di
 * awal SENGAJA belum dibangun (butuh data model baru dari nol, ditunda
 * sampai ada kebutuhan konkret). Default rentang tanggal 30 hari ke depan
 * dari hari ini, sesuai gambaran awal diskusi ("misalkan yang ditampilkan
 * itu 30 hari").
 */
class ScheduleAdminController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfDay();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : $from->copy()->addDays(29)->endOfDay();

        $teacherId = $request->query('teacher_user_id');

        $teachers = User::whereHas('roles', function ($query) {
            $query->where('slug', 'teacher')->where('roles.status', Role::STATUS_ACTIVE);
        })->orderBy('name')->get();

        $sessions = ClassSession::whereBetween('requested_at', [$from, $to])
            ->when($teacherId, fn ($query) => $query->where('teacher_user_id', $teacherId))
            ->with(['student', 'teacher', 'coursePackage', 'branch'])
            ->orderBy('requested_at')
            ->get()
            ->groupBy(fn (ClassSession $session) => optional($session->requested_at)->format('Y-m-d'));

        return view('schedule.index', [
            'sessions' => $sessions,
            'teachers' => $teachers,
            'from' => $from,
            'to' => $to,
            'teacherId' => $teacherId,
        ]);
    }
}
