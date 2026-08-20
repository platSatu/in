<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDivisionUser extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'company_division_user';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_division_id',
        'user_id',
        'status',
    ];

    /**
     * Divisi yang diikuti user.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(
            CompanyDivision::class,
            'company_division_id',
            'id'
        );
    }

    /**
     * User yang tergabung dalam divisi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    /**
     * Scope status aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
