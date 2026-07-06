<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deposit extends Model
{
    use HasUuids;

    protected $table = 'deposits';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'debit',
        'kredit',
        'balance',
        'description',
        'payment_status',
        'payment_method',
        'payment_date',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Relationship ke User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}