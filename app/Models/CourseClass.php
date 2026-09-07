<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Master data program/subjek kursus (mis. Chinese Adult, Chinese Kids,
 * Conversation Master Class). Dirujuk oleh CoursePackage lewat
 * course_class_id.
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
        'status',
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
