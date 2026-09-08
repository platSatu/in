<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\UniversityApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman konfirmasi/ringkasan 1 Aplikasi Kuliah milik siswa yang login
 * (fase 4). Sengaja masih sederhana (belum ada upload dokumen di sini --
 * itu fase 5), cuma menampilkan nomor aplikasi & ringkasan pilihan yang
 * baru saja disubmit, supaya redirect setelah Apply punya tempat mendarat
 * yang jelas.
 */
class ApplicationController extends Controller
{
    public function show(Request $request, string $applicationId): View
    {
        $application = UniversityApplication::with(['universityProfile', 'university', 'student'])
            ->findOrFail($applicationId);

        // Otorisasi kepemilikan: aplikasi ini cuma boleh dilihat oleh siswa
        // yang Student-nya terhubung ke User yang sedang login -- BUKAN
        // dicek lewat permission superadmin manapun (siswa memang tidak
        // pernah punya permission apapun), cukup pencocokan langsung
        // student_id <-> user_id. Siswa lain (atau siapapun yang bukan
        // pemilik) mendapat 403, tidak bisa lihat aplikasi orang lain hanya
        // dengan menebak-nebak ID di URL.
        $user = $request->user();
        $ownsApplication = $application->student && $application->student->user_id === $user->id;

        abort_unless($ownsApplication, Response::HTTP_FORBIDDEN);

        return view('student-portal.applications.show', [
            'application' => $application,
        ]);
    }
}
