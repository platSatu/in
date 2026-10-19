<?php

namespace App\Services\InaStudy;

use App\Helpers\ApplicationFiles;
use App\Models\ApplicationDocument;
use App\Models\DocumentType;
use App\Models\UniversityApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Tambah 1 file ke jenis dokumen yang boleh banyak (DocumentType::allows_multiple,
 * mis. Any Certificate), 13 Oktober 2026. Dipakai halaman upload student &
 * halaman admin supaya aturannya sama: maksimal DocumentType::MAX_MULTIPLE_FILES
 * file per aplikasi (dicek di dalam lock aplikasi), status awal pending.
 */
class MultipleDocumentUploader
{
    private const FOLDER = 'applications/documents';

    /**
     * @throws ValidationException kalau batas jumlah file sudah tercapai
     */
    public function add(UniversityApplication $application, DocumentType $type, UploadedFile $file, string $title, ?string $userId, string $errorKey): ApplicationDocument
    {
        return DB::transaction(function () use ($application, $type, $file, $title, $userId, $errorKey) {
            UniversityApplication::whereKey($application->id)->lockForUpdate()->first();

            $count = ApplicationDocument::where('application_id', $application->id)
                ->where('document_type_id', $type->id)
                ->count();

            if ($count >= DocumentType::MAX_MULTIPLE_FILES) {
                throw ValidationException::withMessages([
                    $errorKey => "Maximum ".DocumentType::MAX_MULTIPLE_FILES." files for {$type->label}.",
                ]);
            }

            return ApplicationDocument::create([
                'application_id' => $application->id,
                'document_type_id' => $type->id,
                'title' => $title,
                'file_path' => $this->store($file),
                'original_filename' => $file->getClientOriginalName(),
                'uploaded_by_user_id' => $userId,
                'uploaded_at' => now(),
                'review_status' => ApplicationDocument::REVIEW_PENDING,
            ]);
        });
    }

    private function store(UploadedFile $file): string
    {
        return ApplicationFiles::store($file, self::FOLDER);
    }
}
