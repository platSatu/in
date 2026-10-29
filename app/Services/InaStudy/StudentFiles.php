<?php

namespace App\Services\InaStudy;

use App\Helpers\ApplicationFiles;
use App\Helpers\DataScope;
use App\Models\Student;
use App\Models\UniversityApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menu Students > File Student (29 Oktober 2026): semua file yang diunggah student
 * (dokumen aplikasi kuliah + foto formulir), dikumpulkan per student untuk backup.
 *
 * - Hanya student yang punya file yang ditampilkan.
 * - Aplikasi Cancelled TETAP ikut (ini backup), foldernya diberi tanda "(Cancelled)".
 * - Akses data mengikuti aturan yang sama dengan Data Student (DataScope cabang/divisi).
 * - File dibaca lewat App\Helpers\ApplicationFiles (folder privat), tidak pernah lewat path dari input user.
 */
class StudentFiles
{
    /** Student yang boleh dilihat user ini dan punya minimal 1 file. */
    public function query(User $user, ?string $search = null): Builder
    {
        return Student::query()
            ->with('companyBranch')
            ->whereHas('applications', fn (Builder $application) => $application->where(fn (Builder $w) => $w
                ->whereHas('documents')
                ->orWhereHas('formDetail', fn (Builder $form) => $form->whereNotNull('photo_path')->where('photo_path', '!=', ''))))
            ->tap(fn (Builder $query) => DataScope::applyBranchDivisionScope($query, $user, 'handled_by_user_id'))
            ->when(filled($search), fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('application_no', 'like', "%{$search}%")
                ->orWhereHas('applications', fn (Builder $a) => $a->where('application_no', 'like', "%{$search}%"))))
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    /**
     * Semua file 1 student yang benar-benar ada di disk.
     *
     * @return Collection<int, array{folder: string, name: string, path: string, size: int, time: int, cancelled: bool}>
     */
    public function files(Student $student): Collection
    {
        $applications = $student->applications()
            ->with(['university:id,name', 'formDetail', 'documents' => fn ($q) => $q->with('documentType')->orderBy('uploaded_at')])
            ->orderBy('created_at')
            ->get();

        return $applications->flatMap(function (UniversityApplication $application) {
            $folder = $this->applicationFolder($application);
            $cancelled = $application->status === UniversityApplication::STATUS_CANCELLED;

            $documents = $application->documents->map(fn ($document) => [
                'relative' => $document->file_path,
                'label' => $document->displayLabel(),
                'original' => $document->original_filename,
                'time' => $document->uploaded_at,
            ]);

            $photo = filled($application->formDetail?->photo_path)
                ? collect([['relative' => $application->formDetail->photo_path, 'label' => 'Foto Formulir', 'original' => null, 'time' => $application->formDetail->updated_at]])
                : collect();

            return $documents->concat($photo)
                ->map(function (array $file) use ($folder, $cancelled) {
                    $path = ApplicationFiles::path($file['relative']);

                    return $path ? [
                        'folder' => $folder,
                        'name' => $this->fileName($file['label'], $file['original'], $path),
                        'path' => $path,
                        'size' => (int) filesize($path),
                        'time' => $file['time']?->getTimestamp() ?? filemtime($path),
                        'cancelled' => $cancelled,
                    ] : null;
                })
                ->filter();
        })->values();
    }

    /** Ringkasan untuk tabel: jumlah aplikasi, jumlah file, ukuran, upload terakhir. */
    public function summary(Student $student): array
    {
        $files = $this->files($student);

        return [
            'applications' => $files->pluck('folder')->unique()->count(),
            'files' => $files->count(),
            'size' => (int) $files->sum('size'),
            'last' => $files->max('time'),
        ];
    }

    /** Nama folder student di dalam ZIP, mis. "Budi-Santoso_INA-2026-012". */
    public function studentFolder(Student $student): string
    {
        $name = trim($student->first_name.' '.$student->last_name) ?: 'Student';

        return $this->safe(Str::slug($name).($student->application_no ? '_'.$student->application_no : '_'.Str::substr($student->id, 0, 8)));
    }

    /** Folder per aplikasi: "Universitas - Jurusan", ditandai kalau Cancelled. */
    private function applicationFolder(UniversityApplication $application): string
    {
        $parts = array_filter([$application->university?->name, $application->major_label !== '-' ? $application->major_label : null]);
        $folder = $parts ? implode(' - ', $parts) : 'Aplikasi '.($application->application_no ?: Str::substr($application->id, 0, 8));

        if ($application->status === UniversityApplication::STATUS_CANCELLED) {
            $folder .= ' (Cancelled)';
        }

        return $this->safe($folder);
    }

    /** "Jenis Dokumen - nama asli.pdf" (ekstensi selalu dari file sebenarnya). */
    private function fileName(string $label, ?string $original, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $base = $original ? pathinfo($original, PATHINFO_FILENAME) : null;

        return $this->safe(($base && $base !== $label ? $label.' - '.$base : $label)).($extension ? '.'.$extension : '');
    }

    /** Buang karakter yang tidak boleh ada di nama file/folder Windows & ZIP. */
    private function safe(string $value): string
    {
        $value = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/', ' ', $value));

        return Str::limit(preg_replace('/\s+/', ' ', $value), 120, '') ?: 'file';
    }
}
