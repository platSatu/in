<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\Student;
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

        // Fase 5 lanjutan: siswa sebelumnya tidak punya cara balik lagi
        // melihat status Aplikasi Kuliah yang sudah disubmit (cuma bisa
        // lewat link redirect sekali pas submit) -- sekarang ditampilkan
        // sebagai widget di dashboard umum ini, kosong/tidak tampil sama
        // sekali untuk user yang tidak punya Student/aplikasi (staff biasa),
        // jadi aman ditambahkan di sini tanpa mengganggu tampilan mereka.
        $student = Student::where('user_id', $userId)->first();
        $myApplications = $student
            ? $student->applications()->with(['university', 'universityProfile'])->orderByDesc('submitted_at')->get()
            : collect();

        return view('dashboard.index', compact('calendars', 'myApplications'));
    }
}
