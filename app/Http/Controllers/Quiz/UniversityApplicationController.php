<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationDocumentHistory;
use App\Models\DocumentType;
use App\Models\University;
use App\Models\UniversityApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fase 5 (superadmin) -- daftar & detail Aplikasi Kuliah siswa, termasuk
 * dokumen yang sudah disubmit (preview/download).
 *
 * Awalnya SENGAJA view-only (index/show/download* di bawah, ability
 * 'view'). Per diskusi "checklist dokumen" 10 September 2026, ditambah
 * ability 'edit' (reviewDocument/uploadDocument) supaya admin bisa
 * approve/reject dokumen & upload dokumen atas nama siswa -- lihat grup
 * route permission 'quiz.university-application,edit' terpisah di
 * routes/web.php, key permission-nya tetap SAMA dengan yang view-only
 * (mengikuti pola modul CRUD lain: 1 key, 2 ability).
 */
class UniversityApplicationController extends Controller
{
    // Sama persis dengan StudentPortal\ApplicationDocumentController --
    // dipakai admin buat upload/upload-ulang dokumen atas nama siswa.
    private string $uploadFolder = 'applications/documents';

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
        // withCount('documents') aman dipakai sebagai "jumlah jenis dokumen
        // yang sudah diupload" TANPA distinct tambahan -- application_documents
        // punya unique constraint (application_id, document_type_id), jadi
        // tiap jenis dokumen memang cuma bisa muncul 1 baris per aplikasi
        // (lihat migration create_application_documents_table).
        $query = UniversityApplication::with(['student', 'university', 'universityProfile'])
            ->withCount('documents');

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

        // Dipakai bareng documents_count di atas untuk progress "x / total"
        // + badge "Documents Complete" di kolom Documents (lihat
        // index.blade.php) -- total jenis dokumen berlaku sama untuk semua
        // aplikasi (DocumentType belum per-university/per-major).
        $totalDocumentTypes = DocumentType::active()->count();

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
            'status',
            'totalDocumentTypes'
        ));
    }

    public function show(string $id)
    {
        $application = UniversityApplication::with(['student', 'university', 'universityProfile', 'handledBy'])
            ->findOrFail($id);

        $documentTypes = DocumentType::active()->orderBy('sort_order')->get();

        $existingDocuments = ApplicationDocument::where('application_id', $application->id)
            ->with(['histories.uploadedBy', 'histories.reviewedBy', 'uploadedBy', 'reviewedBy'])
            ->get()
            ->keyBy('document_type_id');

        $groupedTypes = $documentTypes->groupBy('group_label');

        // FASE 2 -- riwayat transaksi Registration Fee & Departure Fee
        // aplikasi ini, ditampilkan di card "Payment" (lihat show.blade.php)
        // supaya admin bisa lihat status pembayaran TANPA harus buka
        // dashboard gateway terpisah.
        $payments = $application->payments()->latest('created_at')->get();

        return view('quiz.university-application.show', compact(
            'application',
            'groupedTypes',
            'existingDocuments',
            'payments'
        ));
    }

    /**
     * Admin approve/reject satu dokumen yang sudah diupload. Ability 'edit'
     * (lihat catatan permission di docblock class ini). Reject WAJIB disertai
     * catatan -- ditampilkan ke siswa di halaman upload dokumennya supaya
     * jelas apa yang perlu diperbaiki, bukan cuma badge "Rejected" tanpa
     * alasan.
     */
    public function reviewDocument(Request $request, string $id, string $documentId): RedirectResponse
    {
        $document = ApplicationDocument::where('application_id', $id)->findOrFail($documentId);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:2000|required_if:action,reject',
        ], [
            'note.required_if' => 'Alasan reject wajib diisi.',
        ]);

        $document->update([
            'review_status' => $validated['action'] === 'approve'
                ? ApplicationDocument::REVIEW_APPROVED
                : ApplicationDocument::REVIEW_REJECTED,
            'review_note' => $validated['action'] === 'approve' ? ($validated['note'] ?? null) : $validated['note'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('quiz.university-application.show', $id)
            ->with('success', $validated['action'] === 'approve' ? 'Dokumen di-approve.' : 'Dokumen di-reject.');
    }

    /**
     * Admin upload/upload-ulang dokumen ATAS NAMA siswa (mis. siswa kirim
     * file lewat WhatsApp, admin yang unggah ke sistem) -- alur & aturan
     * file sama persis dengan
     * StudentPortal\ApplicationDocumentController::update() (termasuk versi
     * lama disalin ke ApplicationDocumentHistory dulu sebelum ditimpa),
     * bedanya cuma uploaded_by_user_id-nya jadi admin yang login & status
     * review-nya di-reset ke pending lagi (dokumen "baru", perlu direview
     * ulang) sama seperti alur upload ulang siswa.
     */
    public function uploadDocument(Request $request, string $id, string $documentTypeId): RedirectResponse
    {
        $application = UniversityApplication::findOrFail($id);
        $documentType = DocumentType::active()->findOrFail($documentTypeId);

        $extensions = implode(',', $documentType->allowedExtensionsArray());
        $validated = $request->validate([
            'document' => "required|file|max:5120|mimes:{$extensions}",
        ]);

        $existing = ApplicationDocument::where('application_id', $application->id)
            ->where('document_type_id', $documentTypeId)
            ->first();

        if ($existing) {
            ApplicationDocumentHistory::create([
                'application_document_id' => $existing->id,
                'file_path' => $existing->file_path,
                'original_filename' => $existing->original_filename,
                'uploaded_by_user_id' => $existing->uploaded_by_user_id,
                'uploaded_at' => $existing->uploaded_at,
                'replaced_at' => now(),
                'review_status' => $existing->review_status,
                'review_note' => $existing->review_note,
                'reviewed_by_user_id' => $existing->reviewed_by_user_id,
                'reviewed_at' => $existing->reviewed_at,
            ]);
        }

        $relativePath = $this->storeDocumentFile($validated['document']);

        ApplicationDocument::updateOrCreate(
            [
                'application_id' => $application->id,
                'document_type_id' => $documentTypeId,
            ],
            [
                'file_path' => $relativePath,
                'original_filename' => $validated['document']->getClientOriginalName(),
                'uploaded_by_user_id' => $request->user()->id,
                'uploaded_at' => now(),
                'review_status' => ApplicationDocument::REVIEW_PENDING,
                'review_note' => null,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
            ]
        );

        return redirect()
            ->route('quiz.university-application.show', $id)
            ->with('success', 'Dokumen berhasil diupload.');
    }

    /**
     * FASE 2 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) --
     * admin isi manual nominal Registration Fee (registration_fee_amount)
     * DAN Departure Fee (REUSE kolom deposit_fee_china_amount yang sudah
     * ada, lihat migration add_admission_status_to_university_applications_
     * table) untuk 1 aplikasi. Sengaja 1 form gabungan (bukan 2 endpoint
     * terpisah) karena keduanya sama-sama muncul di 1 card "Pembayaran" di
     * halaman detail aplikasi.
     *
     * Nominal yang sudah pernah dibayar (ada ApplicationPayment berstatus
     * 'paid' untuk purpose itu) SENGAJA tetap boleh diedit di sini -- form
     * ini cuma mengubah UniversityApplication::registration_fee_amount /
     * deposit_fee_china_amount (dipakai untuk transaksi BERIKUTNYA kalau
     * ada), TIDAK menyentuh baris application_payments yang sudah selesai.
     */
    public function updateFees(Request $request, string $id): RedirectResponse
    {
        $application = UniversityApplication::findOrFail($id);

        $validated = $request->validate([
            'registration_fee_amount' => 'nullable|integer|min:0',
            'deposit_fee_china_amount' => 'nullable|integer|min:0',
        ]);

        $application->update([
            'registration_fee_amount' => $validated['registration_fee_amount'] ?? null,
            'deposit_fee_china_amount' => $validated['deposit_fee_china_amount'] ?? null,
        ]);

        return redirect()
            ->route('quiz.university-application.show', $id)
            ->with('success', 'Nominal pembayaran berhasil disimpan.');
    }

    /**
     * FASE 4 -- admin ubah admission_status manual jadi PROCESSING atau
     * ACCEPTED. UNDER_REVIEW SENGAJA tidak ada di pilihan form ini -- status
     * itu hanya diset otomatis oleh webhook begitu Registration Fee lunas
     * (lihat FormPaymentController::onApplicationPaymentPaid()), bukan
     * pilihan manual admin (kalau admin butuh "mundurkan" status,
     * cukup pilih kosongkan lewat opsi "- Belum diatur -" di bawah).
     */
    public function updateAdmissionStatus(Request $request, string $id): RedirectResponse
    {
        $application = UniversityApplication::findOrFail($id);

        $validated = $request->validate([
            'admission_status' => ['nullable', 'in:' . implode(',', [
                UniversityApplication::ADMISSION_STATUS_UNDER_REVIEW,
                UniversityApplication::ADMISSION_STATUS_PROCESSING,
                UniversityApplication::ADMISSION_STATUS_ACCEPTED,
            ])],
        ]);

        $application->update([
            'admission_status' => $validated['admission_status'] ?? null,
        ]);

        return redirect()
            ->route('quiz.university-application.show', $id)
            ->with('success', 'Admission status berhasil diperbarui.');
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

    /**
     * Pindahkan file upload ke public/{uploadFolder} dan kembalikan path
     * relatifnya -- sama persis dengan
     * StudentPortal\ApplicationDocumentController::storeDocumentFile(),
     * dipisah di sini karena controller ini beda namespace.
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
