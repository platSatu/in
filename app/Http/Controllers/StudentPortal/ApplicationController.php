<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationPayment;
use App\Models\DocumentType;
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

        // FASE 4 -- dokumen yang DIUPLOAD ADMIN untuk siswa ini (Offer
        // Letter, Passport), ditampilkan sebagai daftar download di card
        // "Documents from InaStudy" (lihat show.blade.php). Siswa TIDAK
        // PERNAH upload jenis dokumen ini sendiri -- lihat
        // DocumentType::scopeAdminProvided() &
        // StudentPortal\ApplicationDocumentController (yang justru
        // MENGECUALIKAN jenis ini dari halaman upload siswa).
        $adminDocumentTypes = DocumentType::active()->adminProvided()->orderBy('sort_order')->get();

        $adminDocuments = ApplicationDocument::where('application_id', $application->id)
            ->whereIn('document_type_id', $adminDocumentTypes->pluck('id'))
            ->get()
            ->keyBy('document_type_id');

        // FASE 5 -- status Departure Fee, dipakai buat card "Departure Fee"
        // di bawah (lihat show.blade.php). Card itu sendiri cuma tampil
        // begitu admission_status === 'accepted' (gerbangnya sudah dicek
        // ganda di ApplicationPaymentController::show()/init(), ini cuma
        // buat tampilan).
        $departureFeePaid = ApplicationPayment::where('application_id', $application->id)
            ->where('purpose', ApplicationPayment::PURPOSE_DEPARTURE_FEE)
            ->where('status', ApplicationPayment::STATUS_PAID)
            ->exists();

        return view('student-portal.applications.show', [
            'application' => $application,
            'adminDocumentTypes' => $adminDocumentTypes,
            'adminDocuments' => $adminDocuments,
            'departureFeePaid' => $departureFeePaid,
        ]);
    }
}
