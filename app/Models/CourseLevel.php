<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Master data level kemampuan (mis. Beginner / Intermediate / Advanced).
 * Dirujuk oleh CoursePackage lewat course_level_id.
 */
class CourseLevel extends Model
{
    use HasUuids;

    protected $table = 'course_level';

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
        return $this->hasMany(CoursePackage::class, 'course_level_id');
    }
}
