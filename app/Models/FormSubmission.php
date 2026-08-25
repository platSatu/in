<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FormSubmission extends Model
{
    use HasUuids;

    protected $table = 'form_submissions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'form_id',
        'status',
        'is_timeout_partial',
        'start_at',
    ];

    protected $casts = [
        'is_timeout_partial' => 'boolean',
        'start_at' => 'datetime',
    ];

    /**
     * Relasi ke Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Relasi kebalikan dari Student::formSubmissions() — kolom `user_id` di
     * tabel ini isinya ID Student (diisi dari $student->id di
     * FrontendController::formWizardSubmit()/formWizardTimeoutSave()), BUKAN
     * ID User/akun admin, meski nama kolomnya "user_id". Sebelumnya method
     * ini salah dinamai `user()` dan salah menunjuk ke User::class — tidak
     * pernah dipakai di mana pun (makanya lolos tanpa ketahuan), sementara
     * FormController::submissions()/saveResult() dan view
     * quiz/form/submissions.blade.php dari awal sudah memanggil
     * $submission->student, jadi selalu gagal dengan RelationNotFoundException.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    /**
     * Relasi kebalikan dari FormPayment::formSubmission() — satu submission
     * maksimal terhubung ke 1 payment (form_submission_id baru diisi setelah
     * submit berhasil, lihat FrontendController). Dipakai di StudentController
     * untuk menampilkan kolom "Pembayaran" tanpa N+1 query.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(FormPayment::class, 'form_submission_id');
    }

    /**
     * Relasi kebalikan dari FormResult::formSubmission() — satu submission
     * maksimal punya 1 hasil (mode 'auto' dibuat otomatis di
     * FrontendController::finalizeCompletedSubmission(), mode 'manual' baru
     * ada setelah admin mengisi lewat FormController::saveResult()). Sama
     * seperti student(), method ini sebelumnya tidak pernah ada padahal
     * sudah dipanggil lewat ->with(['student', 'payment', 'result']) di
     * FormController::submissions().
     */
    public function result(): HasOne
    {
        return $this->hasOne(FormResult::class, 'form_submission_id');
    }

    /**
     * Durasi pengerjaan dalam detik (start_at sampai created_at/waktu submit
     * selesai) -- null kalau start_at tidak tercatat (mis. submission lama
     * sebelum kolom ini ada, atau JS gagal mengirim nilainya). Murni informasi
     * tambahan buat laporan admin, tidak dipakai di logic penilaian mana pun.
     */
    public function workDurationSeconds(): ?int
    {
        if (!$this->start_at || !$this->created_at) {
            return null;
        }

        return abs($this->created_at->getTimestamp() - $this->start_at->getTimestamp());
    }
}