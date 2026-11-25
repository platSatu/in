<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Tarif Pengajar (25 November 2026) -- 1 baris = 1 aturan fee honor.
 *
 *   Pengajar      Cabang        Course Class   Cara Hitung   Fee
 *   (semua)       MOI           Private        Per Kelas     1.000.000
 *   (semua)       Pluit         Private        Per Kelas     2.000.000
 *   Pengajar A    (semua)       (semua)        Per Jam          10.000
 *
 * Kolom yang kosong berarti "semua". Kalau beberapa aturan cocok, yang
 * PALING SPESIFIK menang: menyebut pengajar > menyebut course class >
 * menyebut cabang. Tidak ada yang cocok = fee umum di Course Class.
 */
class TeacherRate extends Model
{
    use HasUuids;

    protected $table = 'teacher_rates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'teacher_user_id',
        'branch_id',
        'course_class_id',
        'honor_mode',
        'fee',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
    ];

    /**
     * Aturan terbaik untuk 1 kelas yang diajar, dari daftar aturan yang sudah dimuat
     * (dimuat sekali per rekap supaya tidak query berulang). null = pakai fee umum Course Class.
     *
     * @param  Collection<int, TeacherRate>  $rates
     */
    public static function bestFor(Collection $rates, ?string $teacherId, ?string $branchId, ?string $courseClassId): ?self
    {
        return $rates
            ->filter(fn (self $rate) => ($rate->teacher_user_id === null || $rate->teacher_user_id === $teacherId)
                && ($rate->branch_id === null || $rate->branch_id === $branchId)
                && ($rate->course_class_id === null || $rate->course_class_id === $courseClassId))
            ->sortByDesc(fn (self $rate) => [$rate->specificity(), $rate->updated_at?->timestamp ?? 0])
            ->first();
    }

    /** Semakin banyak kolom yang diisi, semakin spesifik (pengajar paling berat). */
    public function specificity(): int
    {
        return ($this->teacher_user_id ? 4 : 0) + ($this->course_class_id ? 2 : 0) + ($this->branch_id ? 1 : 0);
    }

    /** Asal angka honor dalam kalimat, mis. "Tarif khusus Pengajar A · Pluit". */
    public function label(): string
    {
        $parts = array_filter([
            $this->teacher ? 'Tarif khusus '.$this->teacher->name : 'Tarif',
            $this->courseClass?->name,
            $this->branch?->name,
        ]);

        return implode(' · ', $parts);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }
}
