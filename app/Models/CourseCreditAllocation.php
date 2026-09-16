<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian asal-usul 1 baris pemotongan (debit) di CourseCredit -- lihat
 * docblock migration create_course_credit_allocations_table untuk latar
 * belakang lengkapnya. 1 baris CourseCredit (debit) bisa punya LEBIH DARI 1
 * baris alokasi di sini kalau porsinya diambil dari beberapa
 * CoursePackagePurchase sekaligus (FIFO, batch tertua duluan).
 */
class CourseCreditAllocation extends Model
{
    use HasUuids;

    protected $table = 'course_credit_allocations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'course_credit_id',
        'course_package_purchase_id',
        'amount',
        'unit_price',
        'value',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'value' => 'decimal:2',
    ];

    public function courseCredit(): BelongsTo
    {
        return $this->belongsTo(CourseCredit::class, 'course_credit_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(CoursePackagePurchase::class, 'course_package_purchase_id');
    }
}
