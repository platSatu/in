<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
    ];

    /**
     * Relasi ke Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    /**
     * Relasi ke User
     *
     * Catatan: di alur submit publik (FrontendController::formWizardSubmit),
     * kolom `user_id` di sini sebenarnya diisi ID dari tabel `students`, bukan
     * `users`. Relasi ini dibiarkan seperti semula (di luar scope perubahan
     * saat ini) supaya tidak mengubah perilaku yang sudah ada; pakai
     * student() di bawah untuk mendapatkan data peserta yang submit.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Peserta (Student) yang mengisi form ini. `user_id` di tabel
     * form_submissions sebenarnya berisi id dari tabel `students`.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    /**
     * Transaksi pembayaran yang terhubung ke submission ini (kalau formnya
     * requires_payment). Null berarti submission ini tidak/belum punya
     * transaksi yang tertaut — dipakai di halaman pembanding submit vs bayar.
     */
    public function payment()
    {
        return $this->hasOne(FormPayment::class, 'form_submission_id');
    }

    /**
     * Hasil placement test submission ini (skor otomatis atau catatan manual
     * dari admin). Null berarti belum ada hasil sama sekali — baik karena
     * form-nya result_mode='none', atau (mode manual) admin belum sempat input.
     */
    public function result()
    {
        return $this->hasOne(FormResult::class, 'form_submission_id');
    }
}