<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu baris = satu slot jadwal kelas kursus milik sebuah Branch (nama,
 * level, tanggal + jam, dan kapasitas peserta). Diisi lewat menu admin
 * Quiz > Class Schedule, lalu ditawarkan ke student lewat link "Pilih
 * Kelas" yang disisipkan ke pesan WhatsApp hasil placement test — lihat
 * existsActiveForBranch() di bawah, dipakai oleh
 * FrontendController::finalizeCompletedSubmission() (mode auto) dan
 * FormController::saveResult() (mode manual).
 */
class ClassSchedule extends Model
{
    use HasUuids;

    protected $table = 'class_schedules';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'level',
        'class_date',
        'start_time',
        'capacity',
        'status',
    ];

    protected $casts = [
        'class_date' => 'date',
        'capacity' => 'integer',
    ];

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    /**
     * Seluruh pendaftaran (semua status, termasuk yang sudah dibatalkan) untuk
     * jadwal ini.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_schedule_id');
    }

    /**
     * Pendaftaran yang masih aktif — dipakai untuk menghitung sisa kuota
     * (capacity - activeEnrollments()->count()) di halaman publik "Pilih
     * Kelas" dan di menu admin Quiz > Class Schedule.
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_schedule_id')->where('status', 'active');
    }

    /**
     * Dipakai FrontendController/FormController untuk cek apakah link "Pilih
     * Kelas" perlu disisipkan ke pesan WA hasil placement test — cukup
     * exists() satu jadwal aktif untuk branch tsb, tidak perlu load semua
     * baris.
     */
    public static function existsActiveForBranch(?string $branchId): bool
    {
        if (!$branchId) {
            return false;
        }

        return static::where('branch_id', $branchId)
            ->where('status', 'active')
            ->exists();
    }
}
