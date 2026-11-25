<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 pengajuan pemakaian credit untuk 1 kelas -- lihat docblock migration
 * create_class_sessions_table untuk alur status lengkapnya, dan
 * App\Services\ClassSession\ClassSessionWorkflowService untuk logika
 * transisi antar statusnya (JANGAN ubah kolom `status` langsung di luar
 * service itu, supaya aturan/pengecekan di tiap transisi selalu dilewati).
 */
class ClassSession extends Model
{
    use HasUuids;

    protected $table = 'class_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_WAITING_TEACHER = 'menunggu_guru';

    public const STATUS_REJECTED_BY_TEACHER = 'ditolak_guru';

    public const STATUS_WAITING_ADMIN = 'menunggu_admin';

    public const STATUS_APPROVED = 'disetujui';

    public const STATUS_REJECTED_BY_ADMIN = 'ditolak_admin';

    // Credit sesi yang sudah disetujui dikembalikan admin (tidak dihitung honor).
    public const STATUS_REFUNDED = 'direfund';

    // Dibatalkan siswa sendiri sebelum pengajar menyetujui (14 Oktober 2026).
    // Tidak ada transaksi: credit belum pernah terpotong & tidak dihitung honor.
    public const STATUS_CANCELLED_BY_STUDENT = 'dibatalkan';

    /** Pengajuan yang masih "memegang" kursi di kelas (belum ditolak/dibatalkan/direfund). */
    public const ACTIVE_STATUSES = [
        self::STATUS_WAITING_TEACHER,
        self::STATUS_WAITING_ADMIN,
        self::STATUS_APPROVED,
    ];

    /**
     * Kapan kelas berlangsung. Pengajuan sebelum fitur Pilih Jadwal
     * (25 November 2026) belum punya class_start_at, jadi memakai waktu pengajuan.
     */
    public function classStartsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->class_start_at ?? $this->requested_at;
    }

    /** Rentang jam kelas untuk ditampilkan, mis. "19:00 - 20:30". */
    public function classHoursLabel(): string
    {
        if (! $this->class_start_at) {
            return optional($this->requested_at)->format('H:i') ?? '-';
        }

        return $this->class_start_at->format('H:i').' - '.optional($this->class_end_at)->format('H:i');
    }

    /**
     * Siswa yang memilih pengajar, paket, dan jam mulai yang sama belajar di
     * KELAS YANG SAMA. Kunci ini dipakai untuk menghitung kuota terisi dan
     * jam mengajar (7 siswa di kelas 1 jam = pengajar tetap 1 jam).
     */
    public function classKey(): string
    {
        if (! $this->class_start_at) {
            return 'session:'.$this->id; // data lama: 1 pengajuan = 1 kelas
        }

        return $this->teacher_user_id.'|'.$this->course_package_id.'|'.$this->class_start_at->format('Y-m-d H:i');
    }

    /** Lama kelas dalam jam (1.5 = 1 jam 30 menit). */
    public function classHours(): float
    {
        if ($this->class_start_at && $this->class_end_at) {
            return round(abs($this->class_start_at->diffInMinutes($this->class_end_at)) / 60, 2);
        }

        return (float) ($this->credit_amount_final ?? $this->credit_amount_requested);
    }

    /** Jam dalam kalimat: 1.5 -> "1 jam 30 menit", 12 -> "12 jam". */
    public static function hoursLabel(float $hours): string
    {
        $minutes = (int) round($hours * 60);
        $label = intdiv($minutes, 60).' jam';

        return $minutes % 60 ? $label.' '.($minutes % 60).' menit' : $label;
    }

    /** Kelas yang berlangsung di rentang waktu ini (dipakai menu Jadwal & Honor Pengajar). */
    public function scopeHeldBetween(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereRaw('COALESCE(class_start_at, requested_at) BETWEEN ? AND ?', [$from, $to]);
    }

    /** Urutkan berdasarkan jam kelas. */
    public function scopeOrderByClassTime(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderByRaw('COALESCE(class_start_at, requested_at) '.($direction === 'desc' ? 'desc' : 'asc'));
    }

    /** Siswa hanya boleh membatalkan selama pengajar belum menyetujui. */
    public function canBeCancelledByStudent(): bool
    {
        return $this->status === self::STATUS_WAITING_TEACHER;
    }

    protected $fillable = [
        'student_id',
        'teacher_user_id',
        'course_package_id',
        'branch_id',
        'credit_amount_requested',
        'credit_amount_final',
        'status',
        'notes',
        'course_credit_id',
        'requested_at',
        'class_start_at',
        'class_end_at',
        'teacher_approved_at',
        'admin_approved_at',
        'admin_user_id',
        'is_admin_entry',
        'refunded_at',
        'refunded_by_user_id',
        'refund_reason',
        'refund_course_credit_id',
    ];

    protected $casts = [
        'credit_amount_requested' => 'decimal:2',
        'credit_amount_final' => 'decimal:2',
        'requested_at' => 'datetime',
        'class_start_at' => 'datetime',
        'class_end_at' => 'datetime',
        'teacher_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'is_admin_entry' => 'boolean',
        'refunded_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function courseCredit(): BelongsTo
    {
        return $this->belongsTo(CourseCredit::class, 'course_credit_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by_user_id');
    }
}
