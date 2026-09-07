<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Master data format kelas kursus (mis. Private / Semi-Private). Dirujuk
 * oleh CoursePackage lewat course_type_id. Model diletakkan FLAT di
 * App\Models (tanpa sub-namespace) meski Controller & View-nya ada di
 * folder Course/, karena model ini akan dipanggil dari banyak tempat lain
 * (mis. modul konversi kredit di kemudian hari).
 */
class CourseType extends Model
{
    use HasUuids;

    protected $table = 'course_type';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function packages()
    {
        return $this->hasMany(CoursePackage::class, 'course_type_id');
    }
}
