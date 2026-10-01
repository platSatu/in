<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Periode cut-off honor pengajar per cabang (dibuat manual admin di menu
 * Honor Pengajar). Selama terbuka, rekap dihitung langsung dari sesi yang
 * disetujui; saat ditutup, rekapnya dikunci ke TeacherHonor. Lihat
 * App\Services\TeacherHonor\TeacherHonorService.
 */
class TeacherHonorPeriod extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'teacher_honor_periods';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'branch_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'created_by_user_id',
        'closed_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /** Periode di cabang ini yang mencakup tanggal ini. */
    public static function covering(?string $branchId, \DateTimeInterface $date): Builder
    {
        return static::where('branch_id', $branchId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    /** Periode yang sudah ditutup dan mencakup tanggal ini di cabang ini (honornya sudah terkunci). */
    public static function closedCovers(?string $branchId, \DateTimeInterface $date): bool
    {
        return static::covering($branchId, $date)->where('status', self::STATUS_CLOSED)->exists();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function honors(): HasMany
    {
        return $this->hasMany(TeacherHonor::class, 'teacher_honor_period_id');
    }
}
