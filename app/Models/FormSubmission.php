<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
    ];

    protected $casts = [
        'is_timeout_partial' => 'boolean',
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
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
}