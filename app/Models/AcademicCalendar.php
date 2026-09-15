<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendar extends Model
{
    use HasUuids;

    protected $table = 'academic_calendars';

    protected $fillable = [
        'user_id',
        // FIX (15 September 2026, permintaan user): siapa yang boleh lihat
        // entry ini -- lihat docblock migration
        // add_target_role_and_branch_to_academic_calendars_table & catatan
        // di DashboardController::index(). Null di salah satu/keduanya
        // berarti "semua" untuk dimensi itu.
        'target_role_id',
        'target_branch_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'event_type',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Role yang jadi target entry ini (null = semua role) -- lihat
     * absensi.academic-calendar.create/edit untuk dropdown "Untuk Role".
     */
    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    /**
     * Branch yang jadi target entry ini (null = semua branch) -- lihat
     * absensi.academic-calendar.create/edit untuk dropdown "Untuk Branch".
     */
    public function targetBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'target_branch_id');
    }
}
