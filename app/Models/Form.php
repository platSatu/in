<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Form extends Model
{
    use HasUuids;

    protected $table = 'forms';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'slug',
        'booth_slug',
        'no_booth',
        'requires_payment',
        'payment_amount',
        'payment_position',
        'is_callback_enabled',
        'callback_link',
        'use_whatsapp_notification',
        'whatsapp_template_id',
        'has_personal_data_stage',
        'result_mode',
        'description',
        'status',
        'start_date',
        'end_date',
        'timer_enabled',
        'timer_duration_minutes',
        'timer_auto_save',
        'timer_auto_restart',
    ];

    protected $casts = [
        'requires_payment' => 'boolean',
        'payment_amount' => 'decimal:2',
        'is_callback_enabled' => 'boolean',
        'use_whatsapp_notification' => 'boolean',
        'has_personal_data_stage' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'view_count' => 'integer',
        'timer_enabled' => 'boolean',
        'timer_duration_minutes' => 'integer',
        'timer_auto_save' => 'boolean',
        'timer_auto_restart' => 'boolean',
    ];

    public function whatsappTemplate()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    /**
     * Relasi ke company branch tempat form/booth ini terdaftar.
     */
    public function companyBranch()
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    /**
     * Seluruh submission (pengisian) yang masuk untuk form ini.
     */
    public function formSubmissions()
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    /**
     * Seluruh percobaan transaksi pembayaran (semua status: pending/paid/
     * failed/expired) untuk form ini. Dipakai untuk total "Payment" di index
     * dan halaman detail submission (data pembanding submit vs bayar).
     */
    public function formPayments()
    {
        return $this->hasMany(FormPayment::class, 'form_id');
    }

    /**
     * Seluruh hasil (FormResult) dari submission-submission form ini. Dipakai
     * untuk laporan/rekap di luar per-submission (mis. rata-rata skor).
     */
    public function formResults()
    {
        return $this->hasMany(FormResult::class, 'form_id');
    }

    /**
     * Scope: form yang boleh diakses lewat URL publik.
     *
     * Ada 2 kondisi (keduanya harus terpenuhi):
     * 1. status harus 'active'.
     * 2. Kalau start_date/end_date diisi, waktu sekarang harus berada di
     *    antara keduanya. Kalau salah satu/keduanya null berarti tidak ada
     *    batasan jadwal di sisi itu.
     */
    public function scopePubliclyAccessible($query)
    {
        $now = now();

        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
    }
}
