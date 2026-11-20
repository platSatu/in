<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 pemakaian kode voucher paket saat checkout (20 November 2026). Baris TIDAK pernah dihapus: pembayaran yang
 * gagal/kedaluwarsa cukup tidak dihitung lagi (lihat applyActive), jadi riwayatnya tetap lengkap.
 */
class CourseVoucherRedemption extends Model
{
    use HasUuids;

    protected $table = 'course_voucher_redemptions';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Baris tanpa pembayaran (proses checkout terputus) dianggap batal setelah sekian menit. */
    public const UNATTACHED_GRACE_MINUTES = 15;

    protected $fillable = [
        'course_voucher_id',
        'user_id',
        'student_id',
        'course_package_id',
        'course_package_payment_id',
        'price_before',
        'discount_amount',
        'price_after',
    ];

    protected $casts = [
        'price_before' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'price_after' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(CourseVoucher::class, 'course_voucher_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CoursePackagePayment::class, 'course_package_payment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function coursePackage(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'course_package_id');
    }

    /**
     * Pemakaian yang masih "memegang" kuota:
     * - pembayaran lunas, ATAU
     * - pembayaran masih menunggu dan belum lewat batas waktunya, ATAU
     * - belum tertaut pembayaran tapi baru dibuat (checkout sedang berjalan).
     * Pembayaran gagal/kedaluwarsa/dibatalkan otomatis tidak dihitung, jadi kuotanya kembali.
     */
    public static function applyActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('payment', fn (Builder $p) => $p->where('status', CoursePackagePayment::STATUS_PAID))
                ->orWhereHas('payment', fn (Builder $p) => $p->where('status', CoursePackagePayment::STATUS_PENDING)
                    ->where(fn (Builder $e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->orWhere(fn (Builder $n) => $n->whereNull('course_package_payment_id')
                    ->where('created_at', '>', now()->subMinutes(self::UNATTACHED_GRACE_MINUTES)));
        });
    }

    /** Status tampilan di halaman admin. */
    public function statusLabel(): string
    {
        $payment = $this->payment;

        return match (true) {
            $payment?->status === CoursePackagePayment::STATUS_PAID => 'Lunas',
            $payment?->status === CoursePackagePayment::STATUS_PENDING && (! $payment->expires_at || $payment->expires_at->isFuture()) => 'Menunggu bayar',
            $payment === null && $this->created_at?->gt(now()->subMinutes(self::UNATTACHED_GRACE_MINUTES)) => 'Diproses',
            default => 'Batal (kuota kembali)',
        };
    }
}
