<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\UniversityApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman "InaStudy" (menu "InaStudy" di sidebar siswa): widget "My University Applications".
 *
 * 9 November 2026: tombol Register manual (tanpa Registration Fee) untuk SISWA dihapus beserta route
 * POST /inastudy/register -- siswa yang belum punya aplikasi diarahkan ke Apply (katalog universitas,
 * wajib bayar Registration Fee). Registrasi manual tanpa bayar kini HANYA lewat admin: tombol
 * "Add to InaStudy" di index Student (Student\StudentController::addToInaStudy).
 */
class InaStudyController extends Controller
{
    public function index(): View
    {
        $student = Student::where('user_id', Auth::id())->first();

        $myApplications = $student
            // Aplikasi yang sudah di-Cancel admin tidak ditampilkan ke siswa (20 Oktober 2026).
            ? $student->applications()->where('status', '!=', UniversityApplication::STATUS_CANCELLED)
                ->with(['university', 'universityProfile'])->withDocumentProgress()->withProgressFlags()->orderByDesc('submitted_at')->get()
            : collect();

        // Staff biasa tanpa data Student tidak melihat widget ini.
        $hasStudent = (bool) $student;

        return view('student-portal.inastudy.index', compact('myApplications', 'hasStudent'));
    }
}
