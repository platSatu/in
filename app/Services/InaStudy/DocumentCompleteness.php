<?php

namespace App\Services\InaStudy;

use App\Models\ApplicationDocument;
use App\Models\ApplicationPayment;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\UniversityApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kelengkapan dokumen student (29 Oktober 2026), dipakai pengingat & kolom "Kelengkapan" di File Student.
 *
 * - Dokumen WAJIB = yang wajib menurut Dokumen Persyaratan kampus aplikasinya (12 November 2026);
 *   kampus yang belum diatur memakai is_required jenis dokumen seperti sebelumnya.
 *   Lihat UniversityApplication::documentTypes().
 * - Dianggap ada kalau sudah diunggah dan TIDAK berstatus ditolak (yang ditolak harus diunggah ulang).
 * - Aplikasi yang diingatkan: tidak Cancelled, Registration Fee lunas, dan Formulir sudah disubmit
 *   (sama dengan syarat halaman upload dokumen terbuka untuk student). Hari ke-0 = saat Formulir disubmit.
 */
class DocumentCompleteness
{
    /** @return Collection<int, DocumentType> */
    public function requiredTypes(UniversityApplication $application): Collection
    {
        return $application->documentTypes()->where('is_required', true)->values();
    }

    /** Label dokumen wajib yang belum ada, mis. ["Ijazah SMA", "Transkrip Nilai"]. */
    public function missing(UniversityApplication $application): Collection
    {
        $documents = $application->relationLoaded('documents') ? $application->documents : $application->documents()->get();
        $present = $documents->where('review_status', '!=', ApplicationDocument::REVIEW_REJECTED)->pluck('document_type_id')->unique();

        return $this->requiredTypes($application)->reject(fn (DocumentType $type) => $present->contains($type->id))->pluck('label')->values();
    }

    public function isComplete(UniversityApplication $application): bool
    {
        return $this->requiredTypes($application)->isNotEmpty() && $this->missing($application)->isEmpty();
    }

    /** Hari ke-0 pengingat: saat student menyetujui & submit Formulir. */
    public function startDate(UniversityApplication $application): ?Carbon
    {
        $date = $application->formDetail?->terms_accepted_at;

        return $date ? Carbon::parse($date)->timezone(config('app.timezone')) : null;
    }

    /** Aplikasi yang halaman upload dokumennya sudah terbuka untuk student. */
    public function openApplications(): Builder
    {
        return UniversityApplication::query()
            ->where('status', '!=', UniversityApplication::STATUS_CANCELLED)
            ->whereHas('formDetail', fn (Builder $f) => $f->whereNotNull('terms_accepted_at'))
            ->whereHas('payments', fn (Builder $p) => $p
                ->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE)
                ->where('status', ApplicationPayment::STATUS_PAID));
    }

    /**
     * Ringkasan 1 student untuk tabel File Student, dari aplikasi yang tidak Cancelled.
     *
     * @return array{status: string, missing: int}  status: complete | incomplete | none
     */
    public function studentStatus(Student $student): array
    {
        $applications = $student->applications()
            ->where('status', '!=', UniversityApplication::STATUS_CANCELLED)
            ->with('documents:id,application_id,document_type_id,review_status')
            ->get();

        $applications = $applications->filter(fn (UniversityApplication $application) => $this->requiredTypes($application)->isNotEmpty());

        if ($applications->isEmpty()) {
            return ['status' => 'none', 'missing' => 0];
        }

        $missing = $applications->sum(fn (UniversityApplication $application) => $this->missing($application)->count());

        return ['status' => $missing === 0 ? 'complete' : 'incomplete', 'missing' => $missing];
    }
}
