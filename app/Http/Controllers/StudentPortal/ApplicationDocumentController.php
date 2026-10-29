<?php

namespace App\Http\Controllers\StudentPortal;

use App\Helpers\ApplicationFiles;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationDocumentHistory;
use App\Models\ApplicationFormDetail;
use App\Models\ApplicationPayment;
use App\Models\DocumentType;
use App\Models\UniversityApplication;
use App\Services\InaStudy\DocumentCompleteness;
use App\Services\InaStudy\DocumentNotifier;
use App\Services\InaStudy\MultipleDocumentUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function edit(Request $request, string $applicationId): View|RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        // FASE 2 (Alur Pembayaran 2 Arah) -- Step 1 (Study Plan, lihat
        // group_label "Academic" di DocumentTypeSeeder) & Step 2 (Upload
        // Documents, halaman ini) BARU boleh diakses setelah Registration Fee
        // lunas. Kalau belum, arahkan balik ke halaman pembayaran alih-alih
        // menampilkan 403 -- lebih ramah karena ini alur normal (siswa baru
        // submit Apply), bukan percobaan akses tidak sah.
        if ($redirect = $this->blockIfRegistrationFeeUnpaid($application)) {
            return $redirect;
        }

        // FASE 3 -- Step 2 (halaman ini) baru boleh diakses setelah Step 1
        // (Formulir web, lihat ApplicationFormController) selesai dicentang
        // Terms & Condition-nya. Dicek lewat kolom terms_accepted_at, BUKAN
        // sekadar baris ApplicationFormDetail ada/tidak (siswa bisa saja
        // sempat isi sebagian tanpa submit).
        if ($redirect = $this->blockIfFormNotSubmitted($application)) {
            return $redirect;
        }

        // FASE 4 -- dokumen provided_by='admin' (Offer Letter, Passport)
        // SENGAJA tidak ikut ditampilkan sebagai kolom upload di sini --
        // siswa cuma bisa MELIHAT/DOWNLOAD dokumen itu di halaman ringkasan
        // aplikasi (student-portal.applications.show), tidak pernah upload
        // sendiri. Lihat DocumentType::scopeStudentUpload().
        $documentTypes = DocumentType::active()->studentUpload()->orderBy('sort_order')->get();

        $documents = ApplicationDocument::where('application_id', $application->id)
            ->with(['histories.uploadedBy', 'documentType'])
            ->orderBy('uploaded_at')
            ->get();

        $groupedTypes = $documentTypes->groupBy('group_label');

        return view('student-portal.applications.documents', [
            'application' => $application,
            'groupedTypes' => $groupedTypes,
            // Jenis biasa: 1 file per jenis. Jenis allows_multiple: daftar file.
            'existingDocuments' => $documents->keyBy('document_type_id'),
            'multipleDocuments' => $documents->groupBy('document_type_id'),
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

        // Sama seperti guard di edit() -- dicek lagi di sini (bukan cuma di
        // edit()) supaya submit langsung ke endpoint POST ini (mis. lewat
        // form yang sempat ke-cache/dibuka dari tab lama) tidak bisa
        // melewati gerbang pembayaran.
        if ($redirect = $this->blockIfRegistrationFeeUnpaid($application)) {
            return $redirect;
        }

        // Sama seperti guard di edit() -- lihat blockIfFormNotSubmitted().
        if ($redirect = $this->blockIfFormNotSubmitted($application)) {
            return $redirect;
        }

        // FASE 4 -- sama seperti edit(): dibatasi ke studentUpload() supaya
        // POST manual (mis. lewat curl/devtools) tidak bisa menyelundupkan
        // documents.{admin_document_type_id} dan menimpa dokumen yang
        // seharusnya cuma admin yang boleh isi.
        $documentTypes = DocumentType::active()->studentUpload()->get()->keyBy('id');

        // Rule validasi dibangun dinamis per DocumentType supaya ekstensi
        // yang diizinkan (mis. Passport & Pass Photo cuma jpg/jpeg) tetap
        // ditegakkan per-jenis, bukan satu rule mimes generik untuk semua.
        $rules = [];
        foreach ($documentTypes as $documentType) {
            $extensions = implode(',', $documentType->allowedExtensionsArray());

            if ($documentType->allows_multiple) {
                // Jenis boleh banyak (mis. Any Certificate): baris baru = nama + file.
                $rules["certificates.{$documentType->id}"] = 'nullable|array|max:'.DocumentType::MAX_MULTIPLE_FILES;
                $rules["certificates.{$documentType->id}.*.title"] = "nullable|string|max:150|required_with:certificates.{$documentType->id}.*.file";
                $rules["certificates.{$documentType->id}.*.file"] = "nullable|file|max:5120|mimes:{$extensions}";
            } else {
                $rules["documents.{$documentType->id}"] = "nullable|file|max:5120|mimes:{$extensions}";
            }
        }

        $validated = $request->validate($rules, [
            'certificates.*.*.title.required_with' => 'Please fill in the certificate name for each file.',
        ]);
        $uploadedFiles = array_filter($validated['documents'] ?? []);
        $newMultiple = collect($validated['certificates'] ?? [])
            ->map(fn ($rows) => collect($rows)->filter(fn ($row) => ! empty($row['file']))->values())
            ->filter(fn ($rows) => $rows->isNotEmpty());

        if (empty($uploadedFiles) && $newMultiple->isEmpty()) {
            return redirect()
                ->route('student-portal.applications.documents.edit', $application->id)
                ->with('status', 'No file was selected to upload.');
        }

        $userId = Auth::id();

        // Unique (aplikasi, jenis) sudah tidak di database (lihat migration
        // allow_multiple_application_documents) -- untuk jenis biasa tetap 1
        // file per aplikasi, dijaga dengan lock aplikasi di sini.
        DB::transaction(function () use ($application, $uploadedFiles, $documentTypes, $userId) {
        UniversityApplication::whereKey($application->id)->lockForUpdate()->first();

        foreach ($uploadedFiles as $documentTypeId => $file) {
            $documentType = $documentTypes->get($documentTypeId);

            if (! $documentType || $documentType->allows_multiple) {
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
                    // Ikut simpan hasil review versi lama (kalau sempat direview
                    // sebelum di-upload ulang) -- supaya tidak hilang begitu
                    // baris ApplicationDocument-nya di-reset ke pending di bawah.
                    'review_status' => $existing->review_status,
                    'review_note' => $existing->review_note,
                    'reviewed_by_user_id' => $existing->reviewed_by_user_id,
                    'reviewed_at' => $existing->reviewed_at,
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
        });

        $uploader = new MultipleDocumentUploader();

        foreach ($newMultiple as $documentTypeId => $rows) {
            $documentType = $documentTypes->get($documentTypeId);

            if (! $documentType || ! $documentType->allows_multiple) {
                continue;
            }

            foreach ($rows as $row) {
                $uploader->add($application, $documentType, $row['file'], trim($row['title']), $userId, "certificates.{$documentTypeId}");
            }
        }

        $this->notifyAdminsAfterResponse($application, $uploadedFiles, $newMultiple, $documentTypes);

        return redirect()
            ->route('student-portal.applications.documents.edit', $application->id)
            ->with('success', 'Documents uploaded successfully. Our team will review them shortly.');
    }

    /**
     * Hapus 1 file dari jenis yang boleh banyak (mis. Any Certificate) -- hanya
     * milik student sendiri dan selama belum di-approve (pending/rejected).
     */
    public function destroy(Request $request, string $applicationId, string $documentId): RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        if ($redirect = $this->blockIfRegistrationFeeUnpaid($application)) {
            return $redirect;
        }

        $document = ApplicationDocument::where('application_id', $application->id)
            ->with('documentType')
            ->findOrFail($documentId);

        abort_unless($document->documentType?->allows_multiple && $document->canBeDeletedByStudent(), Response::HTTP_FORBIDDEN);

        $filePath = $document->file_path;
        $document->delete();
        ApplicationFiles::delete($filePath);

        return redirect()
            ->route('student-portal.applications.documents.edit', $application->id)
            ->with('success', 'File removed.');
    }

    /**
     * Notifikasi Dokumen (29 Oktober 2026, Students > File Student > tab Notifikasi):
     * info ke admin bahwa student baru mengunggah dokumen. Dijalankan SETELAH respons
     * terkirim ke student (app()->terminating), jadi upload tidak ikut lambat menunggu
     * gateway WA/email, dan kalau pengiriman gagal upload tetap berhasil.
     */
    private function notifyAdminsAfterResponse(UniversityApplication $application, array $uploadedFiles, $newMultiple, $documentTypes): void
    {
        $labels = collect(array_keys($uploadedFiles))
            ->map(fn ($id) => $documentTypes->get($id))
            ->filter(fn ($type) => $type && ! $type->allows_multiple)
            ->map(fn ($type) => $type->label)
            ->concat(collect($newMultiple)->flatMap(fn ($rows, $id) => collect($rows)->map(
                fn ($row) => ($documentTypes->get($id)->label ?? 'Document').': '.trim((string) ($row['title'] ?? ''))
            )))
            ->values()
            ->all();

        $applicationId = $application->id;

        app()->terminating(function () use ($applicationId, $labels) {
            try {
                $fresh = UniversityApplication::with(['student', 'university', 'documents'])->find($applicationId);
                if ($fresh) {
                    $notifier = app(DocumentNotifier::class);
                    $notifier->documentsUploaded($fresh, $labels);
                    // Card 3: kalau upload ini membuat dokumen wajib lengkap, kirim terima kasih & info admin (sekali).
                    $notifier->notifyIfComplete($fresh, app(DocumentCompleteness::class));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    /**
     * FASE 2 -- null kalau Registration Fee aplikasi ini sudah lunas (boleh
     * lanjut), atau RedirectResponse ke halaman pembayaran kalau belum.
     * Dicek lewat tabel application_payments (BUKAN cuma
     * admission_status), karena admission_status baru diisi oleh webhook
     * SETELAH pembayaran ini juga -- lihat
     * FormPaymentController::onApplicationPaymentPaid().
     */
    private function blockIfRegistrationFeeUnpaid(UniversityApplication $application): ?RedirectResponse
    {
        $isPaid = ApplicationPayment::where('application_id', $application->id)
            ->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE)
            ->where('status', ApplicationPayment::STATUS_PAID)
            ->exists();

        if ($isPaid) {
            return null;
        }

        return redirect()
            ->route('student-portal.applications.payment.show', [$application->id, ApplicationPayment::PURPOSE_REGISTRATION_FEE])
            ->with('status', 'Selesaikan pembayaran Registration Fee terlebih dahulu untuk membuka Study Plan & Upload Documents.');
    }

    /**
     * FASE 3 -- null kalau Formulir (Step 1, lihat ApplicationFormController)
     * sudah disubmit (terms_accepted_at terisi), atau RedirectResponse ke
     * halaman Formulir kalau belum.
     */
    private function blockIfFormNotSubmitted(UniversityApplication $application): ?RedirectResponse
    {
        $isSubmitted = ApplicationFormDetail::where('application_id', $application->id)
            ->whereNotNull('terms_accepted_at')
            ->exists();

        if ($isSubmitted) {
            return null;
        }

        return redirect()
            ->route('student-portal.applications.form.edit', $application->id)
            ->with('status', 'Silakan lengkapi Formulir terlebih dahulu sebelum upload dokumen.');
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
        // Aplikasi yang dibatalkan admin tidak bisa dibuka/dibayar/diisi lagi.
        abort_if($application->isCancelled(), Response::HTTP_NOT_FOUND);

        return $application;
    }

    /**
     * Pindahkan file upload ke public/{uploadFolder} dan kembalikan path
     * relatifnya, mengikuti pola
     * Quiz\UniversityAlbumPhotoController::storePhotoFile().
     */
    private function storeDocumentFile($file): string
    {
        return ApplicationFiles::store($file, ApplicationFiles::DOCUMENTS);
    }

    /**
     * Buka file dokumen milik aplikasi siswa sendiri (termasuk dokumen dari
     * InaStudy). File privat, tidak ada URL publik -- 19 Oktober 2026.
     */
    public function file(Request $request, string $applicationId, string $documentId)
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);
        $document = ApplicationDocument::where('application_id', $application->id)->findOrFail($documentId);

        return ApplicationFiles::response($document->file_path, $document->original_filename);
    }

    public function historyFile(Request $request, string $applicationId, string $historyId)
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);
        $history = ApplicationDocumentHistory::with('applicationDocument')->findOrFail($historyId);

        abort_unless($history->applicationDocument?->application_id === $application->id, Response::HTTP_NOT_FOUND);

        return ApplicationFiles::response($history->file_path, $history->original_filename);
    }
}
