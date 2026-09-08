<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Counter nomor aplikasi per periode (period_key format "YYMM", mis.
 * "2609" = September 2026). Dipakai App\Services\ApplicationNumberGenerator
 * (fase 4) dengan lockForUpdate() supaya aman dari race condition kalau ada
 * beberapa submit bersamaan dalam periode yang sama.
 */
class ApplicationNumberCounter extends Model
{
    protected $fillable = [
        'period_key',
        'last_sequence',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
    ];
}
