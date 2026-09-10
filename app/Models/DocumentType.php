<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jenis dokumen yang bisa diupload calon siswa per Aplikasi Kuliah
 * (Passport, Transcript, dst) -- data konfigurasi, lihat DocumentTypeSeeder.
 */
class DocumentType extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // FIX (10 September 2026): fitur "Alur Pembayaran 2 Arah Apply Kampus" --
    // bedakan dokumen yang diupload SISWA (default, semua DocumentType yang
    // sudah ada tidak berubah) dari dokumen yang diupload ADMIN untuk
    // didownload siswa (Offer Letter, Passport -- section terpisah
    // "Documents from Admin", view-only, tidak ada kolom upload di sisi
    // siswa untuk jenis dokumen ini).
    public const PROVIDED_BY_STUDENT = 'student';
    public const PROVIDED_BY_ADMIN = 'admin';

    protected $fillable = [
        'code',
        'label',
        'group_label',
        'allowed_extensions',
        // FIX (10 September 2026): fitur "Download Template" -- opsional per
        // jenis dokumen, path relatif ke public/ (contoh:
        // "document-templates/medical_certificate.pdf"). Lihat
        // resources/views/student-portal/applications/documents.blade.php.
        'template_file_path',
        // FIX (10 September 2026): lihat catatan PROVIDED_BY_* di atas.
        'provided_by',
        'is_required',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Daftar ekstensi yang diizinkan sebagai array (mis. ['jpg','jpeg']),
     * dipecah dari kolom CSV allowed_extensions.
     */
    public function allowedExtensionsArray(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->allowed_extensions)));
    }

    public function applicationDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'document_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // FIX (10 September 2026): dipakai halaman upload SISWA (Fase 4) supaya
    // jenis dokumen yang provided_by='admin' (Offer Letter, Passport) tidak
    // ikut muncul sebagai kolom upload di checklist siswa -- ditampilkan di
    // section terpisah "Documents from Admin" (view-only) sebagai gantinya.
    public function scopeStudentUpload($query)
    {
        return $query->where('provided_by', self::PROVIDED_BY_STUDENT);
    }

    // FIX (10 September 2026): kebalikan dari scopeStudentUpload() di atas --
    // dipakai buat query section "Documents from Admin".
    public function scopeAdminProvided($query)
    {
        return $query->where('provided_by', self::PROVIDED_BY_ADMIN);
    }
}
