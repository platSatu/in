<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ledger saldo credit kursus 1 student -- pola kolom (debit/kredit/balance,
 * running balance per baris) SENGAJA disamakan dengan App\Models\Deposit,
 * lihat docblock migration create_course_credits_table untuk alasan
 * lengkapnya.
 *
 * FASE 1 "Fondasi Pelacakan Asal Credit" (16 September 2026): `source_type`
 * menandai kenapa 1 baris ini ada (lihat docblock migration
 * add_source_type_to_course_credits_table), dan relasi `allocations()`
 * menunjuk ke rincian asal-pembelian tiap baris DEBIT (lihat
 * App\Models\CourseCreditAllocation & App\Services\CourseCredit\
 * CourseCreditDebitService yang membuatnya) -- baris KREDIT (masuk dari
 * beli/klaim package) tetap cukup pakai `course_package_purchase_id`
 * langsung seperti sebelumnya, tidak perlu allocations sama sekali.
 */
class CourseCredit extends Model
{
    use HasUuids;

    protected $table = 'course_credits';

    protected $keyType = 'string';

    public $incrementing = false;

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_SESSION_DEBIT = 'session_debit';

    public const SOURCE_TRADE_IN_DEBIT = 'trade_in_debit';

    protected $fillable = [
        'student_id',
        'course_package_purchase_id',
        'source_type',
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

    public function allocations(): HasMany
    {
        return $this->hasMany(CourseCreditAllocation::class, 'course_credit_id');
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
