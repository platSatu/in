<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AcademicCalendar extends Model
{
    use HasUuids;

    protected $table = 'academic_calendars';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'event_type',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
