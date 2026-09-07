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

    /**
     * Saldo TERKINI 1 user, diambil dari kolom `balance` baris deposit
     * terakhirnya (setiap baris deposit sudah menyimpan running balance
     * setelah baris itu, bukan cuma debit/kredit-nya sendiri).
     *
     * Query & urutan sort (payment_date lalu created_at) SENGAJA disamakan
     * persis dengan yang dipakai di
     * App\Http\Controllers\Dashboard\DepositController &
     * App\Http\Controllers\Dashboard\DepositWebhookController supaya
     * "saldo yang ditampilkan" dan "saldo dasar buat topup berikutnya" tidak
     * pernah berbeda sumber.
     */
    public static function currentBalanceFor(?string $userId): float
    {
        if ($userId === null || trim($userId) === '') {
            return 0.0;
        }

        $last = static::query()
            ->where('user_id', $userId)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->first();

        return (float) ($last?->balance ?? 0);
    }
}