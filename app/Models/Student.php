<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'sales_id',
        'handled_by_user_id',
        'branch_id',
        'company_division_id',
        'form_id',
        'images',
        'first_name',
        'last_name',
        'email',
        'handphone',
        'status',
    ];

    /**
     * Akun login (User) milik student ini, kalau sudah dibuat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Marketing/pengajar yang menangani student ini — dipakai utk filter
     * scope 'self' (lihat App\Concerns\HasScopedAccess::isSelfScopedOnly()).
     * Diisi manual saat create/edit, atau di-assign ulang oleh manager;
     * BUKAN sales_id (yang tetap teks bebas/kode referral dari wizard publik).
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id', 'id');
    }

    /**
     * Branch terakhir yang diisi student ini (lihat catatan di migration
     * add_branch_and_form_to_students_table).
     */
    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    /**
     * Divisi student ini, dipakai utk filter scope 'division'.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(CompanyDivision::class, 'company_division_id');
    }

    /**
     * Form terakhir yang diisi student ini.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Seluruh riwayat pengisian quiz student ini. Di alur wizard publik
     * (FrontendController::formWizardSubmit), FormSubmission.user_id diisi
     * dengan id student yang submit — jadi relasi ini yang jadi sumber "history
     * lengkap", terlepas dari branch_id/form_id di atas yang cuma nyimpan
     * singgahan terakhir.
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'user_id');
    }

    /**
     * Seluruh riwayat pendaftaran kelas (kursus Mandarin, dst) milik student
     * ini. Ditambahkan untuk fitur Portal Siswa -- dipakai buat cek apakah
     * menu "Kursus Saya" (InaYule) perlu ditampilkan (relasi lama
     * ClassEnrollment.student_id sudah ada, ini cuma tambahan inverse-nya
     * di sisi Student, tidak mengubah apapun di ClassEnrollment).
     */
    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'student_id');
    }

    /**
     * Seluruh Aplikasi Kuliah (apply ke kampus) milik student ini.
     * Ditambahkan untuk fitur Apply Kampus / Portal Siswa (InaStudy) --
     * tabel university_applications baru, tidak mengubah tabel Student.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(UniversityApplication::class, 'student_id');
    }
}