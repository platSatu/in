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
        'company_division_id',
        'name',
        'form_type',
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
        'collect_identity',
        'personal_data_stage_position',
        'result_mode',
        'section_fail_threshold',
        'section_pass_threshold',
        'description',
        'pre_test_notice',
        'background_image',
        'logo',
        'status',
        'start_date',
        'end_date',
        'max_participants',
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
        'collect_identity' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'view_count' => 'integer',
        'max_participants' => 'integer',
        'timer_enabled' => 'boolean',
        'timer_duration_minutes' => 'integer',
        'timer_auto_save' => 'boolean',
        'timer_auto_restart' => 'boolean',
        'section_fail_threshold' => 'integer',
        'section_pass_threshold' => 'integer',
    ];

    /** Jenis form: menentukan menu admin tempat submission-nya ditampilkan. Bawaan 'general'. */
    public const TYPE_GENERAL = 'general';
    public const TYPE_INA_VISA = 'ina_visa';

    public const TYPES = [
        self::TYPE_GENERAL => 'Umum',
        self::TYPE_INA_VISA => 'Ina Visa',
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
     * Divisi tempat form/booth ini terdaftar, dipakai utk filter scope 'division'.
     */
    public function division()
    {
        return $this->belongsTo(CompanyDivision::class, 'company_division_id');
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
     * Jumlah peserta = student BERBEDA yang sudah submit form ini (submit ulang /
     * percobaan timeout dari orang yang sama dihitung sekali; count(distinct) memakai
     * indeks (form_id, user_id)), ditambah submission tanpa identitas (form dengan
     * collect_identity mati). Submission anonim tidak bisa dikenali orangnya, jadi
     * dihitung per submit; simpanan timeout yang nanti diulang (timer_auto_restart)
     * tidak ikut dihitung karena peserta yang sama masih akan submit lagi.
     */
    public function participantCount(): int
    {
        $known = $this->formSubmissions()->whereNotNull('user_id')->distinct()->count('user_id');
        $anonymous = $this->formSubmissions()->whereNull('user_id')
            ->when($this->timer_auto_restart, fn ($q) => $q->where('is_timeout_partial', false))
            ->count();

        return $known + $anonymous;
    }

    /** Versi tanpa query untuk daftar admin (relasi formSubmissions sudah di-eager-load). */
    public function participantCountFromLoaded(): int
    {
        $rows = $this->formSubmissions;

        return $rows->whereNotNull('user_id')->pluck('user_id')->unique()->count()
            + $rows->whereNull('user_id')->filter(fn ($s) => !$this->timer_auto_restart || !$s->is_timeout_partial)->count();
    }

    /** Batas peserta terisi & sudah tercapai: form tertutup (URL tetap bisa dibuka). */
    public function isFull(): bool
    {
        return $this->max_participants !== null && $this->participantCount() >= $this->max_participants;
    }

    /**
     * Boleh menyimpan submission baru untuk student ini? Dipanggil SETELAH baris form
     * dikunci (lockForUpdate) supaya dua submit bersamaan tidak sama-sama lolos di
     * kursi terakhir. Yang tetap boleh walau kuota penuh: student yang sudah tercatat
     * sebagai peserta (tidak menambah jumlah) dan peserta yang sudah membayar.
     */
    public function admits(?string $studentId, bool $paid = false): bool
    {
        if ($this->max_participants === null || $paid) {
            return true;
        }

        return ($studentId !== null && $this->formSubmissions()->where('user_id', $studentId)->exists())
            || $this->participantCount() < $this->max_participants;
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

        // CATATAN (disepakati dengan admin): end_date SENGAJA TIDAK LAGI membatasi
        // akses publik di sini. Kalau admin mengisi Tanggal Selesai tapi form masih
        // ingin tetap tampil setelah tanggal itu lewat, cukup pastikan status tetap
        // 'active' -- status jadi "saklar utama" (master switch), Tanggal
        // Selesai murni catatan rencana admin, bukan auto-expire yang mengunci.
        // start_date TETAP membatasi (form yang belum waktunya tayang tetap
        // disembunyikan sampai tanggalnya tiba, walau status sudah active) --
        // beda semantiknya dengan end_date: "belum waktunya" vs "sudah lewat".
        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            });
    }
}
