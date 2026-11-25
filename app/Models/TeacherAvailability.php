<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jam buka pengajar di satu cabang, contoh: "Senin 13:00 - 17:00".
 * Diisi admin di menu Jadwal > Ketersediaan Pengajar, berlaku setiap minggu.
 * Siswa hanya bisa memilih jam kelas yang ada di dalam jam buka ini.
 */
class TeacherAvailability extends Model
{
    use HasUuids;

    protected $table = 'teacher_availabilities';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Kelas hanya Senin - Sabtu (angka mengikuti Carbon: 1 = Senin). */
    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    protected $fillable = [
        'branch_id',
        'teacher_user_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? '-';
    }

    /** "13:00 - 17:00" */
    public function hoursLabel(): string
    {
        return substr($this->start_time, 0, 5).' - '.substr($this->end_time, 0, 5);
    }
}
