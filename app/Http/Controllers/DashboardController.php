<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Get active academic calendars for the current user
        $calendars = AcademicCalendar::query()
            ->where('user_id', (string) $userId)
            ->where('is_active', true)
            ->get();

        // FIX (14 September 2026, permintaan user -- "dashboard itu hanya
        // tanggal saja"): widget "My University Applications" + alur
        // Register manual yang sebelumnya sempat ditaruh di halaman ini
        // SUDAH DIPINDAH ke halaman terpisah "InaStudy"
        // (App\Http\Controllers\StudentPortal\InaStudyController +
        // resources/views/student-portal/inastudy/index.blade.php), dituju
        // langsung lewat menu "InaStudy" di sidebar -- supaya halaman
        // Dashboard ini murni cuma Academic Calendar seperti semula.
        return view('dashboard.index', compact('calendars'));
    }
}
