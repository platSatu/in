<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu dokumen yang diminta sebuah kampus (12 November 2026), diatur di halaman edit University.
 * Dipakai UniversityApplication::documentTypes() untuk menyusun kolom upload student.
 */
class UniversityDocumentRequirement extends Model
{
    use HasUuids;

    public const DEGREE_SCOPES = ['bachelor' => 'S1 saja', 'postgraduate' => 'S2/S3 saja'];
    public const LANGUAGE_SCOPES = ['english' => 'Program bahasa Inggris', 'chinese' => 'Program bahasa Mandarin'];

    protected $fillable = ['university_id', 'document_type_id', 'is_required', 'note', 'degree_scope', 'language_scope', 'template_file_path'];

    protected $casts = ['is_required' => 'boolean'];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /** Berlaku untuk jenjang & bahasa pengantar aplikasi ini? Data kosong dianggap berlaku (lebih aman ditampilkan). */
    public function appliesTo(UniversityApplication $application): bool
    {
        $degree = strtolower((string) $application->degree);
        $language = strtolower((string) $application->language);
        $postgraduate = in_array($degree, ['master', 'phd'], true);

        $degreeOk = match ($this->degree_scope) {
            'bachelor' => $degree === '' || $degree === 'bachelor',
            'postgraduate' => $degree === '' || $postgraduate,
            default => true,
        };

        $languageOk = match ($this->language_scope) {
            'english' => $language === '' || str_contains($language, 'english') || str_contains($language, 'inggris'),
            'chinese' => $language === '' || str_contains($language, 'chinese') || str_contains($language, 'mandarin'),
            default => true,
        };

        return $degreeOk && $languageOk;
    }
}
