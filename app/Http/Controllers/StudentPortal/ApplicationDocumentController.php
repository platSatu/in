<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationDocumentHistory;
use App\Models\ApplicationFormDetail;
use App\Models\ApplicationPayment;
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

        return redirect()
            ->route('student-portal.applications.documents.edit', $application->id)
            ->with('success', 'Documents uploaded successfully. Our team will review them shortly.');
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
