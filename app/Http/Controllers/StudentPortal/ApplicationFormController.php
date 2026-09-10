<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationEducationBackground;
use App\Models\ApplicationFormDetail;
use App\Models\ApplicationPayment;
use App\Models\UniversityApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * FASE 3 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- Step 1
 * SETELAH Registration Fee lunas: siswa isi "Formulir" (biodata lengkap +
 * Education Background "add row") LANGSUNG di web, mengikuti field persis
 * dari contoh form Word yang dikirim user ("Nanjing Tech Form"), diakhiri
 * checklist Terms & Condition sebelum lanjut ke Step 2 (Upload Documents).
 *
 * DocumentType 'formulir' (upload file manual) dinonaktifkan bersamaan
 * dengan fitur ini -- lihat DocumentTypeSeeder.
 */
class ApplicationFormController extends Controller
{
    private string $photoUploadFolder = 'applications/form-photos';

    public function edit(Request $request, string $applicationId): View|RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        if ($redirect = $this->blockIfRegistrationFeeUnpaid($application)) {
            return $redirect;
        }

        $formDetail = ApplicationFormDetail::where('application_id', $application->id)->first();
        $educationBackgrounds = ApplicationEducationBackground::where('application_id', $application->id)
            ->orderBy('sort_order')
            ->get();

        return view('student-portal.applications.form', [
            'application' => $application,
            'formDetail' => $formDetail,
            'educationBackgrounds' => $educationBackgrounds,
        ]);
    }

    public function update(Request $request, string $applicationId): RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);

        if ($redirect = $this->blockIfRegistrationFeeUnpaid($application)) {
            return $redirect;
        }

        $validated = $request->validate([
            'surname' => 'nullable|string|max:255',
            'given_name' => 'nullable|string|max:255',
            'chinese_name' => 'nullable|string|max:255',
            'photo' => 'nullable|file|max:5120|mimes:jpg,jpeg',
            'gender' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:255',
            'passport_no' => 'nullable|string|max:100',
            'passport_expiry_date' => 'nullable|date',
            'telephone_no' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'hobby' => 'nullable|string|max:255',
            'parents_name' => 'nullable|string|max:255',
            'parents_phone' => 'nullable|string|max:100',
            'parents_occupation' => 'nullable|string|max:255',
            'home_address' => 'nullable|string|max:2000',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:100',
            'highest_degree_obtained' => 'nullable|string|max:255',
            'field_of_study_in_china' => 'nullable|string|max:255',
            'financial_support_by' => 'nullable|string|max:255',
            'sponsorship_type' => ['nullable', Rule::in([
                ApplicationFormDetail::SPONSORSHIP_SCHOLARSHIP,
                ApplicationFormDetail::SPONSORSHIP_SELF_SPONSORED,
            ])],
            // FASE 3: Terms & Condition WAJIB dicentang -- ini satu-satunya
            // field wajib di seluruh form ini (semua biodata di atas sengaja
            // nullable, sama filosofinya dengan DocumentType lain yang semua
            // is_required=false, biar siswa bisa isi bertahap/kembali lagi).
            'terms_accepted' => 'required|accepted',

            'education' => 'nullable|array',
            'education.*.level' => ['nullable', Rule::in(array_keys(ApplicationEducationBackground::LEVELS))],
            'education.*.school_name' => 'nullable|string|max:255',
            'education.*.location' => 'nullable|string|max:255',
            'education.*.year_start' => 'nullable|string|max:10',
            'education.*.year_end' => 'nullable|string|max:10',
        ]);

        $existingFormDetail = ApplicationFormDetail::where('application_id', $application->id)->first();

        $photoPath = $existingFormDetail->photo_path ?? null;
        if ($request->hasFile('photo')) {
            $photoPath = $this->storePhotoFile($request->file('photo'));
        }

        ApplicationFormDetail::updateOrCreate(
            ['application_id' => $application->id],
            [
                'surname' => $validated['surname'] ?? null,
                'given_name' => $validated['given_name'] ?? null,
                'chinese_name' => $validated['chinese_name'] ?? null,
                'photo_path' => $photoPath,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'passport_no' => $validated['passport_no'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'telephone_no' => $validated['telephone_no'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'place_of_birth' => $validated['place_of_birth'] ?? null,
                'hobby' => $validated['hobby'] ?? null,
                'parents_name' => $validated['parents_name'] ?? null,
                'parents_phone' => $validated['parents_phone'] ?? null,
                'parents_occupation' => $validated['parents_occupation'] ?? null,
                'home_address' => $validated['home_address'] ?? null,
                'email' => $validated['email'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'highest_degree_obtained' => $validated['highest_degree_obtained'] ?? null,
                'field_of_study_in_china' => $validated['field_of_study_in_china'] ?? null,
                'financial_support_by' => $validated['financial_support_by'] ?? null,
                'sponsorship_type' => $validated['sponsorship_type'] ?? null,
                'terms_accepted_at' => now(),
            ]
        );

        // FASE 3 -- "add row" Education Background: pola replace-all (hapus
        // semua baris lama punya aplikasi ini, lalu insert ulang dari yang
        // disubmit) -- paling sederhana untuk form dengan baris yang bisa
        // ditambah/dihapus bebas di client, tidak perlu mencocokkan id baris
        // lama satu-satu. Baris kosong total (tidak ada satupun kolom
        // terisi) dilewati supaya tidak nyimpen baris kosong percuma.
        ApplicationEducationBackground::where('application_id', $application->id)->delete();

        $educationRows = $validated['education'] ?? [];
        foreach (array_values($educationRows) as $index => $row) {
            $hasContent = collect($row)->filter(fn ($value) => filled($value))->isNotEmpty();

            if (!$hasContent) {
                continue;
            }

            ApplicationEducationBackground::create([
                'application_id' => $application->id,
                'level' => $row['level'] ?? null,
                'school_name' => $row['school_name'] ?? null,
                'location' => $row['location'] ?? null,
                'year_start' => $row['year_start'] ?? null,
                'year_end' => $row['year_end'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return redirect()
            ->route('student-portal.applications.documents.edit', $application->id)
            ->with('success', 'Formulir berhasil disimpan. Silakan lanjutkan upload dokumen di bawah.');
    }

    /**
     * Sama persis pola blockIfRegistrationFeeUnpaid() di
     * ApplicationDocumentController -- duplikasi SENGAJA (bukan trait/helper
     * bersama) mengikuti konvensi ownedApplicationOrFail() yang juga
     * diduplikasi per controller di seluruh namespace StudentPortal ini.
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
            ->with('status', 'Selesaikan pembayaran Registration Fee terlebih dahulu untuk membuka Formulir & Upload Documents.');
    }

    private function ownedApplicationOrFail(Request $request, string $applicationId): UniversityApplication
    {
        $application = UniversityApplication::with(['university', 'universityProfile', 'student'])
            ->findOrFail($applicationId);

        $user = $request->user();
        $ownsApplication = $application->student && $application->student->user_id === $user->id;

        abort_unless($ownsApplication, Response::HTTP_FORBIDDEN);

        return $application;
    }

    private function storePhotoFile($file): string
    {
        $destination = public_path($this->photoUploadFolder);

        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return $this->photoUploadFolder . '/' . $filename;
    }
}
