<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger saldo credit kursus 1 student -- pola kolom (debit/kredit/balance,
 * running balance per baris) SENGAJA disamakan dengan App\Models\Deposit,
 * lihat docblock migration create_course_credits_table untuk alasan
 * lengkapnya.
 */
class CourseCredit extends Model
{
    use HasUuids;

    protected $table = 'course_credits';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'course_package_purchase_id',
        'debit',
        'kredit',
        'balance',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(CoursePackagePurchase::class, 'course_package_purchase_id');
    }

    /**
     * Saldo credit TERKINI 1 student, diambil dari kolom `balance` baris
     * ledger terakhirnya -- pola & alasan SAMA PERSIS dengan
     * App\Models\Deposit::currentBalanceFor() (baca docblock di sana).
     */
    public static function currentBalanceFor(?string $studentId): float
    {
        if ($studentId === null || trim($studentId) === '') {
            return 0.0;
        }

        $last = static::query()
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->first();

        return (float) ($last?->balance ?? 0);
    }
}
