<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rate komisi TERKINI 1 pengajar -- lihat docblock migration
 * create_teacher_commission_rates_table. Baris ini diupdate di tempat
 * kalau admin ubah persentase; nilai yang SUDAH DIPAKAI untuk menghitung
 * honor selalu disnapshot terpisah di TeacherHonor, tidak pernah
 * dipengaruhi perubahan di sini secara retroaktif.
 */
class TeacherCommissionRate extends Model
{
    use HasUuids;

    protected $table = 'teacher_commission_rates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'teacher_user_id',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    /**
     * Persentase komisi TERKINI seorang pengajar -- default 0.0 kalau
     * belum pernah diatur admin sama sekali (SENGAJA bukan angka tebakan,
     * lihat docblock migration untuk alasannya).
     */
    public static function rateFor(string $teacherUserId): float
    {
        $rate = static::query()->where('teacher_user_id', $teacherUserId)->first();

        return (float) ($rate?->percentage ?? 0.0);
    }
}
