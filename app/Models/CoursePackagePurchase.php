<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histori "apa yang sudah didapat/dibeli" 1 student dari 1 CoursePackage --
 * lihat docblock lengkap di migration create_course_package_purchases_table
 * untuk alasan desainnya.
 *
 * `price_paid` & `credits_granted` adalah SNAPSHOT harga/credits package
 * pada saat baris ini dibuat -- JANGAN dipakai untuk baca harga/credits
 * package "sekarang" (pakai relasi coursePackage() untuk itu), field ini
 * murni untuk riwayat supaya tidak ikut berubah kalau package-nya diedit
 * admin belakangan.
 */
class CoursePackagePurchase extends Model
{
    use HasUuids;

    protected $table = 'course_package_purchases';

    protected $keyType = 'string';

    public $incrementing = false;

    public const SOURCE_TRIAL_CLAIM = 'trial_claim';
    public const SOURCE_DEPOSIT_PURCHASE = 'deposit_purchase';

    // Ditambah bersama fitur checkout package berbayar (16 September 2026) --
    // lihat docblock migration extend_course_package_purchase_source_and_wa_template.
    // 'deposit_purchase' TETAP dipakai kalau 100% tertutup saldo Deposit.
    public const SOURCE_GATEWAY_PURCHASE = 'gateway_purchase';
    public const SOURCE_MIXED_PURCHASE = 'mixed_purchase';

    // FASE 4 "Konversi/Upgrade Paket" (16 September 2026) -- checkout upgrade
    // ke package lain, sisa saldo CourseCredit lama ditukar (trade-in) jadi
    // nilai rupiah untuk menutup sebagian/seluruh harga package baru, lihat
    // App\Services\CoursePackagePayment\PackageUpgradeCalculator. TIDAK
    // butuh migration tambahan -- kolom `source` sudah VARCHAR(30) bebas
    // nilai sejak migration extend_course_package_purchase_source_and_wa_template.
    public const SOURCE_UPGRADE_PURCHASE = 'upgrade_purchase';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'course_package_id',
        'price_paid',
        'credits_granted',
        'source',
        'status',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'credits_granted' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }
}
