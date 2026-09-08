<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationDocumentHistory;
use App\Models\DocumentType;
use App\Models\University;
use App\Models\UniversityApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fase 5 (superadmin) -- daftar & detail Aplikasi Kuliah siswa, termasuk
 * dokumen yang sudah disubmit (preview/download). SENGAJA view-only untuk
 * sekarang: belum ada create/update/delete dari sisi admin di modul ini
 * (lihat catatan permission di routes/web.php, cuma 1 level
 * 'quiz.university-application' tanpa varian ',edit').
 */
class UniversityApplicationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $universityId = $request->query('university_id');
        $intakeYear = $request->query('intake_year');
        $status = $request->query('status');

        // Query manual (bukan AdminCrud::paginate()) karena butuh filter ke
        // beberapa kolom sekaligus (university_id, intake_year, status) +
        // pencarian yang menembus relasi student, mengikuti pola yang sama
        // dipakai UniversityAlbumPhotoController::index().
        $query = UniversityApplication::with(['student', 'university', 'universityProfile']);

        if (!empty($universityId)) {
            $query->where('university_id', $universityId);
        }

        if (!empty($intakeYear)) {
            $query->where('intake_year', $intakeYear);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('application_no', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('handphone', 'like', "%{$search}%");
                    });
            });
        }

        $data = $query->orderByDesc('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $universities = University::orderBy('name')->get();

        $intakeYears = UniversityApplication::whereNotNull('intake_year')
            ->distinct()
            ->orderByDesc('intake_year')
            ->pluck('intake_year');

        $statuses = [
            UniversityApplication::STATUS_SUBMITTED => 'Submitted',
            UniversityApplication::STATUS_DOCUMENTS_REVIEW => 'Documents Review',
            UniversityApplication::STATUS_REGISTERED => 'Registered',
            UniversityApplication::STATUS_VISA_PROCESS => 'Visa Process',
            UniversityApplication::STATUS_CHECK_IN => 'Check In',
            UniversityApplication::STATUS_COMPLETED => 'Completed',
            UniversityApplication::STATUS_CANCELLED => 'Cancelled',
        ];

        return view('quiz.university-application.index', compact(
            'data',
            'universities',
            'intakeYears',
            'statuses',
            'search',
            'universityId',
            'intakeYear',
            'status'
        ));
    }

    public function show(string $id)
    {
        $application = UniversityApplication::with(['student', 'university', 'universityProfile', 'handledBy'])
            ->findOrFail($id);

        $documentTypes = DocumentType::active()->orderBy('sort_order')->get();

        $existingDocuments = ApplicationDocument::where('application_id', $application->id)
            ->with(['histories.uploadedBy', 'uploadedBy'])
            ->get()
            ->keyBy('document_type_id');

        $groupedTypes = $documentTypes->groupBy('group_label');

        return view('quiz.university-application.show', compact(
            'application',
            'groupedTypes',
            'existingDocuments'
        ));
    }

    /**
     * Download file dokumen TERKINI, dengan nama file yang rapi (nama siswa
     * + jenis dokumen), mengikuti pola
     * FrontendController::handbookDownload().
     */
    public function downloadDocument(string $id, string $documentId)
    {
        $document = ApplicationDocument::where('application_id', $id)
            ->with(['application.student', 'documentType'])
            ->findOrFail($documentId);

        return $this->downloadFile(
            $document->file_path,
            $document->application->student ?? null,
            $document->documentType->label ?? 'document',
            $document->original_filename
        );
    }

    /**
     * Download 1 versi LAMA dari sebuah dokumen (ApplicationDocumentHistory).
     */
    public function downloadDocumentHistory(string $id, string $historyId)
    {
        $history = ApplicationDocumentHistory::with(['applicationDocument.application.student', 'applicationDocument.documentType'])
            ->findOrFail($historyId);

        $document = $history->applicationDocument;

        abort_unless($document && $document->application_id === $id, 404);

        return $this->downloadFile(
            $history->file_path,
            $document->application->student ?? null,
            ($document->documentType->label ?? 'document') . '-old',
            $history->original_filename
        );
    }

    private function downloadFile(?string $relativePath, $student, string $label, ?string $originalFilename)
    {
        abort_if(empty($relativePath), 404);

        $path = public_path($relativePath);

        if (!file_exists($path)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $studentName = $student ? trim($student->first_name . ' ' . $student->last_name) : 'student';
        $downloadName = Str::slug($studentName . '-' . $label) . '.' . $extension;

        return response()->download($path, $downloadName);
    }
}
