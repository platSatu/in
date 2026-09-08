<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationDocumentHistory;
use App\Models\DocumentType;
use App\Models\UniversityApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase 5 -- upload dokumen Aplikasi Kuliah (per DocumentType, mis. Passport,
 * Transcript, dst -- lihat DocumentTypeSeeder). Semua jenis dokumen bersifat
 * nullable & bisa diupload ulang kapan saja (resubmit); upload ulang TIDAK
 * menghapus file lama -- file lama disalin dulu ke ApplicationDocumentHistory
 * sebelum baris ApplicationDocument-nya ditimpa, sesuai permintaan eksplisit
 * user supaya versi lama tetap bisa dilihat admin ("iya betul di jadikan
 * history ya").
 *
 * File disimpan LANGSUNG ke public/applications/documents (bukan lewat
 * Storage::disk('public')), mengikuti pola persis
 * Quiz\UniversityAlbumPhotoController::storePhotoFile().
 */
class ApplicationDocumentController extends Controller
{
    private string $uploadFolder = 'applications/documents';

    /**
     * Tampilkan form upload, dikelompokkan per group_label DocumentType,
     * dengan status upload terkini (belum upload / pending / approved /
     * rejected) untuk tiap jenis dokumen.
     */
    public function edit(Request $request, string $applicationId): View
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        $documentTypes = DocumentType::active()->orderBy('sort_order')->get();

        $existingDocuments = ApplicationDocument::where('application_id', $application->id)
            ->with('histories.uploadedBy')
            ->get()
            ->keyBy('document_type_id');

        $groupedTypes = $documentTypes->groupBy('group_label');

        return view('student-portal.applications.documents', [
            'application' => $application,
            'groupedTypes' => $groupedTypes,
            'existingDocuments' => $existingDocuments,
        ]);
    }

    /**
     * Simpan file yang diupload user (hanya field yang benar-benar dipilih
     * yang diproses -- form tidak mewajibkan semua jenis dokumen terisi
     * sekaligus, siswa boleh upload sebagian lalu kembali lagi nanti).
     */
    public function update(Request $request, string $applicationId): RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        $documentTypes = DocumentType::active()->get()->keyBy('id');

        // Rule validasi dibangun dinamis per DocumentType supaya ekstensi
        // yang diizinkan (mis. Passport & Pass Photo cuma jpg/jpeg) tetap
        // ditegakkan per-jenis, bukan satu rule mimes generik untuk semua.
        $rules = [];
        foreach ($documentTypes as $documentType) {
            $extensions = implode(',', $documentType->allowedExtensionsArray());
            $rules["documents.{$documentType->id}"] = "nullable|file|max:5120|mimes:{$extensions}";
        }

        $validated = $request->validate($rules);
        $uploadedFiles = array_filter($validated['documents'] ?? []);

        if (empty($uploadedFiles)) {
            return redirect()
                ->route('student-portal.applications.documents.edit', $application->id)
                ->with('status', 'No file was selected to upload.');
        }

        $userId = Auth::id();

        foreach ($uploadedFiles as $documentTypeId => $file) {
            $documentType = $documentTypes->get($documentTypeId);

            if (! $documentType) {
                continue;
            }

            $existing = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type_id', $documentTypeId)
                ->first();

            // Upload ulang -- pindahkan data versi LAMA ke history dulu
            // sebelum baris ApplicationDocument-nya ditimpa. File fisik lama
            // SENGAJA tidak dihapus (beda dengan pola foto album), supaya
            // link di histories() masih bisa dibuka/didownload admin.
            if ($existing) {
                ApplicationDocumentHistory::create([
                    'application_document_id' => $existing->id,
                    'file_path' => $existing->file_path,
                    'original_filename' => $existing->original_filename,
                    'uploaded_by_user_id' => $existing->uploaded_by_user_id,
                    'uploaded_at' => $existing->uploaded_at,
                    'replaced_at' => now(),
                ]);
            }

            $relativePath = $this->storeDocumentFile($file);

            ApplicationDocument::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'document_type_id' => $documentTypeId,
                ],
                [
                    'file_path' => $relativePath,
                    'original_filename' => $file->getClientOriginalName(),
                    'uploaded_by_user_id' => $userId,
                    'uploaded_at' => now(),
                    'review_status' => ApplicationDocument::REVIEW_PENDING,
                    'review_note' => null,
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                ]
            );
        }

        return redirect()
            ->route('student-portal.applications.documents.edit', $application->id)
            ->with('success', 'Documents uploaded successfully. Our team will review them shortly.');
    }

    /**
     * Otorisasi kepemilikan -- sama persis dengan
     * StudentPortal\ApplicationController::show(): aplikasi hanya boleh
     * diakses siswa yang Student-nya terhubung ke User yang sedang login.
     */
    private function ownedApplicationOrFail(Request $request, string $applicationId): UniversityApplication
    {
        $application = UniversityApplication::with(['university', 'universityProfile', 'student'])
            ->findOrFail($applicationId);

        $user = $request->user();
        $ownsApplication = $application->student && $application->student->user_id === $user->id;

        abort_unless($ownsApplication, Response::HTTP_FORBIDDEN);

        return $application;
    }

    /**
     * Pindahkan file upload ke public/{uploadFolder} dan kembalikan path
     * relatifnya, mengikuti pola
     * Quiz\UniversityAlbumPhotoController::storePhotoFile().
     */
    private function storeDocumentFile($file): string
    {
        $destination = public_path($this->uploadFolder);

        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return $this->uploadFolder . '/' . $filename;
    }
}
