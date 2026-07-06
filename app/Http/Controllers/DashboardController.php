<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use Illuminate\Http\Request;
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
        
        return view('dashboard.index', compact('calendars'));
    }
}
