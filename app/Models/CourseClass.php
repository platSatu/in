<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Jenis kelas kursus (mis. Private, Semi-Private, Group) + Fee Pengajar
 * (Rp) per kelas. Dirujuk oleh CoursePackage lewat course_class_id.
 */
class CourseClass extends Model
{
    use HasUuids;

    protected $table = 'course_class';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'teacher_fee',
        'honor_mode',
        'status',
    ];

    /**
     * Cara hitung honor (20 November 2026). per_class = cara lama (default, fee x jumlah kelas);
     * per_hour = fee x total jam mengajar (1 credit disetujui = 1 jam). Lihat TeacherHonorService.
     */
    public const HONOR_PER_CLASS = 'per_class';

    public const HONOR_PER_HOUR = 'per_hour';

    public const HONOR_MODES = [
        self::HONOR_PER_CLASS => 'Per Kelas',
        self::HONOR_PER_HOUR => 'Per Jam',
    ];

    /** Fee pengajar (Rp) per kelas per periode, atau per jam kalau honor_mode = per_hour. */
    protected $casts = [
        'teacher_fee' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function packages()
    {
        return $this->hasMany(CoursePackage::class, 'course_class_id');
    }
}
