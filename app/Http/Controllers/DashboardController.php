<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\DocumentType;
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
            ? $student->applications()->with(['university', 'universityProfile'])->withCount('documents')->orderByDesc('submitted_at')->get()
            : collect();

        // Sama seperti di halaman admin (Quiz\UniversityApplicationController::index)
        // -- dipakai bareng documents_count di atas untuk progress bar "x / total"
        // dokumen di widget "My University Applications" (lihat diskusi
        // "progress bar student dashboard", 10 September 2026).
        $totalDocumentTypes = DocumentType::active()->count();

        return view('dashboard.index', compact('calendars', 'myApplications', 'totalDocumentTypes'));
    }
}
