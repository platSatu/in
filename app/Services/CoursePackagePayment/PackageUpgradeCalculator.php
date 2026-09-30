<?php

namespace App\Services\CoursePackagePayment;

use App\Helpers\MoneyMath;
use App\Models\ClassSession;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\CourseCredit\CourseCreditDebitService;
use App\Services\CourseCredit\InsufficientCourseCreditException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Upgrade & Convert paket -- satu-satunya tempat aturannya. Credit yang
 * ditukar SELALU dari 1 baris pembelian yang dipilih siswa di tab Status
 * ($source), bukan dari semua paket.
 *
 * - UPGRADE ($quantity null): beli 1 paket penuh. Seluruh sisa credit baris
 *   itu ditukar ke rupiah (harga per credit saat dibeli); kurangnya dibayar
 *   saldo Deposit lalu payment gateway, lebihnya masuk saldo Deposit.
 * - CONVERT ($quantity N): ambil N credit paket tujuan (harga per credit
 *   paket tujuan), maksimal senilai credit asal. Credit asal dipotong per
 *   1 credit utuh; kelebihan nilainya masuk saldo Deposit. Tidak pernah ke
 *   gateway.
 *
 * Saldo Deposit tidak bisa dicairkan, hanya untuk beli paket lagi. Semua
 * angka dihitung ulang di dalam lock (settle()); preview() hanya tampilan.
 * Pembulatan selalu ke bawah (MoneyMath).
 */
class PackageUpgradeCalculator
{
    public function __construct(
        private readonly CourseCreditDebitService $debitService = new CourseCreditDebitService()
    ) {
    }

    /** Alasan baris pembelian ini belum bisa di-upgrade/convert; null = boleh. */
    public function blockedReason(CoursePackagePurchase $source): ?string
    {
        $pendingSessions = ClassSession::where('student_id', $source->student_id)
            ->where('course_package_id', $source->course_package_id)
            ->whereIn('status', [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN])
            ->exists();

        if ($pendingSessions) {
            return 'Masih ada pengajuan kelas untuk paket ini yang belum selesai. Tunggu disetujui/ditolak dulu sebelum upgrade atau convert.';
        }

        if (CoursePackagePayment::awaitingTradeIn()->where('source_course_package_purchase_id', $source->id)->exists()) {
            return 'Credit paket ini sedang dipakai untuk upgrade yang menunggu pembayaran. Selesaikan atau tunggu pembayaran itu kedaluwarsa dulu.';
        }

        if ($this->remaining($source) <= 0.0) {
            return 'Credit paket ini sudah habis.';
        }

        if (CourseCreditDebitService::unitPrice($source) <= 0.0) {
            return 'Credit dari paket gratis/trial tidak punya nilai tukar, jadi tidak bisa di-upgrade atau convert.';
        }

        return null;
    }

    /**
     * Rincian angka tanpa menulis apa pun.
     *
     * @return array<string, mixed> lihat plan() + deposit_balance, deposit_portion, gateway_portion
     *
     * @throws UpgradeConfirmationFailedException kalau quantity convert tidak valid
     */
    public function preview(mixed $user, CoursePackagePurchase $source, CoursePackage $target, ?int $quantity = null): array
    {
        $plan = $this->plan($this->remaining($source), $source, $target, $quantity);
        $depositBalance = Deposit::currentBalanceFor((string) $user->id);
        $depositPortion = MoneyMath::floorToScale(min($depositBalance, $plan['shortfall']), 2);

        return $plan + [
            'deposit_balance' => $depositBalance,
            'deposit_portion' => $depositPortion,
            'gateway_portion' => MoneyMath::floorToScale($plan['shortfall'] - $depositPortion, 2),
        ];
    }

    /**
     * Jalur instan (trade-in + saldo cukup; convert selalu lewat sini).
     * Kalau angka berubah di dalam lock, exception membatalkan SELURUH transaksi.
     *
     * @throws UpgradeConfirmationFailedException
     */
    public function completeInstant(mixed $user, Student $student, CoursePackagePurchase $source, CoursePackage $target, ?int $quantity = null): CoursePackagePayment
    {
        return DB::transaction(function () use ($user, $student, $source, $target, $quantity) {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->firstOrFail();
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();

            if ($reason = $this->blockedReason($source)) {
                throw new UpgradeConfirmationFailedException($reason);
            }

            $plan = $this->plan($this->remaining($source), $source, $target, $quantity);
            $orderId = $this->generateOrderId();
            $result = $this->settle((string) $user->id, $lockedStudent, $source, $target, $plan, $plan['shortfall'], 0.0, $orderId);

            return CoursePackagePayment::create([
                'student_id' => $lockedStudent->id,
                'user_id' => (string) $user->id,
                'course_package_id' => $target->id,
                'course_package_purchase_id' => $result['purchase']->id,
                'source_course_package_purchase_id' => $source->id,
                'order_id' => $orderId,
                'gateway' => null,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'handphone' => $user->handphone,
                'price_total' => $plan['price'],
                'deposit_portion' => $plan['shortfall'],
                'gateway_portion' => 0,
                'credit_trade_in_portion' => $result['trade_in_applied'],
                'trade_in_course_credit_id' => $result['trade_in_debit']->id,
                'credits_granted' => $plan['quantity'],
                'status' => CoursePackagePayment::STATUS_PAID,
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * Buat CoursePackagePayment 'pending' untuk upgrade lewat gateway, di dalam
     * lock student + cek ulang blockedReason, supaya dua submit bersamaan
     * tidak membuat dua pembayaran atas credit yang sama. Credit & saldo
     * belum disentuh -- baru dieksekusi saat webhook (confirmGatewayUpgrade()).
     *
     * @param  array<string, mixed>  $attributes  kolom gateway/kontak dari controller
     *
     * @throws UpgradeConfirmationFailedException
     */
    public function beginGatewayUpgrade(Student $student, CoursePackagePurchase $source, CoursePackage $target, mixed $user, array $attributes): CoursePackagePayment
    {
        return DB::transaction(function () use ($student, $source, $target, $user, $attributes) {
            Student::where('id', $student->id)->lockForUpdate()->firstOrFail();

            if ($reason = $this->blockedReason($source)) {
                throw new UpgradeConfirmationFailedException($reason);
            }

            $preview = $this->preview($user, $source, $target);

            if ($preview['gateway_portion'] <= 0.0) {
                throw new UpgradeConfirmationFailedException('insufficient_deposit');
            }

            return CoursePackagePayment::create($attributes + [
                'student_id' => $student->id,
                'user_id' => (string) $user->id,
                'course_package_id' => $target->id,
                'source_course_package_purchase_id' => $source->id,
                'order_id' => $this->generateOrderId(),
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'handphone' => $user->handphone,
                'price_total' => $preview['price'],
                'deposit_portion' => $preview['deposit_portion'],
                'gateway_portion' => $preview['gateway_portion'],
                // Angka rencana; trade-in sungguhan baru dieksekusi saat webhook.
                'credit_trade_in_portion' => $preview['trade_in_applied'],
                'credits_granted' => $preview['quantity'],
                'status' => CoursePackagePayment::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Konfirmasi upgrade lewat gateway -- dipanggil webhook di dalam
     * transaksinya (payment sudah dikunci). Nilai trade-in dihitung ulang dan
     * tidak boleh lebih kecil dari rencana saat checkout.
     *
     * @return array{purchase: CoursePackagePurchase, trade_in_debit: CourseCredit, trade_in_applied: float}
     *
     * @throws UpgradeConfirmationFailedException
     */
    public function confirmGatewayUpgrade(CoursePackagePayment $lockedPayment): array
    {
        // Urutan lock sama dengan jalur instan: student lalu users.
        $lockedStudent = Student::where('id', $lockedPayment->student_id)->lockForUpdate()->firstOrFail();
        DB::table('users')->where('id', $lockedPayment->user_id)->lockForUpdate()->first();
        $source = CoursePackagePurchase::findOrFail($lockedPayment->source_course_package_purchase_id);
        $target = CoursePackage::findOrFail($lockedPayment->course_package_id);

        $plan = $this->plan($this->remaining($source), $source, $target, null, $lockedPayment);

        if ($plan['trade_in_applied'] + 0.000001 < (float) $lockedPayment->credit_trade_in_portion) {
            throw new UpgradeConfirmationFailedException('insufficient_trade_in');
        }

        return $this->settle(
            (string) $lockedPayment->user_id,
            $lockedStudent,
            $source,
            $target,
            $plan,
            (float) $lockedPayment->deposit_portion,
            (float) $lockedPayment->gateway_portion,
            $lockedPayment->order_id
        );
    }

    /** Paket tujuan harus berbayar & punya credit (paket gratis tidak bisa jadi tujuan tukar). */
    public static function isSellable(CoursePackage $target): bool
    {
        return (float) $target->credits > 0 && $target->effectivePrice() > 0;
    }

    public function generateOrderId(): string
    {
        do {
            $orderId = 'CPPU'.now()->format('ymd').strtoupper(Str::random(8));
        } while (CoursePackagePayment::where('order_id', $orderId)->exists());

        return $orderId;
    }

    /**
     * Hitungan murni.
     *
     * @return array{mode: string, price: float, quantity: float, max_quantity: ?int, source_remaining: float, source_unit_price: float, target_unit_price: float, credits_used: float, trade_in_value: float, trade_in_applied: float, leftover_to_deposit: float, shortfall: float}
     *
     * @throws UpgradeConfirmationFailedException
     */
    private function plan(float $remaining, CoursePackagePurchase $source, CoursePackage $target, ?int $quantity, ?CoursePackagePayment $agreed = null): array
    {
        if (! $agreed && ! self::isSellable($target)) {
            throw new UpgradeConfirmationFailedException('Paket tujuan ini tidak bisa dipakai untuk upgrade/convert.');
        }

        $sourceUnit = CourseCreditDebitService::unitPrice($source);
        $targetUnit = self::isSellable($target) ? MoneyMath::floorToScale($target->effectivePrice() / (float) $target->credits, 4) : 0.0;
        $maxQuantity = $targetUnit > 0 ? (int) floor(MoneyMath::floorToScale($remaining * $sourceUnit, 2) / $targetUnit + 1e-9) : 0;

        if ($quantity === null) {
            // Gateway: pakai harga & credit yang disepakati saat checkout, bukan harga hari ini.
            $price = $agreed ? (float) $agreed->price_total : $target->effectivePrice();
            $grant = $agreed ? (float) $agreed->credits_granted : (float) $target->credits;
            $used = $remaining;
        } else {
            if ($maxQuantity < 1) {
                throw new UpgradeConfirmationFailedException('Nilai sisa credit ini belum cukup untuk 1 credit paket tujuan.');
            }

            if ($quantity < 1 || $quantity > $maxQuantity) {
                throw new UpgradeConfirmationFailedException("Jumlah credit harus antara 1 dan {$maxQuantity}.");
            }

            $price = MoneyMath::floorToScale($quantity * $targetUnit, 2);
            $grant = (float) $quantity;
            // Credit asal dipotong per 1 credit utuh (atau seluruh sisa kalau lebih kecil).
            $used = $sourceUnit > 0 ? min($remaining, ceil($price / $sourceUnit - 1e-9)) : $remaining;
        }

        $value = MoneyMath::floorToScale($used * $sourceUnit, 2);
        $applied = MoneyMath::floorToScale(min($value, $price), 2);

        if ($quantity !== null && $applied < $price) {
            throw new UpgradeConfirmationFailedException('Nilai sisa credit ini belum cukup untuk jumlah tersebut.');
        }

        return [
            'mode' => $quantity === null ? 'upgrade' : 'convert',
            'price' => $price,
            'quantity' => $grant,
            'max_quantity' => $maxQuantity,
            'source_remaining' => $remaining,
            'source_unit_price' => $sourceUnit,
            'target_unit_price' => $targetUnit,
            'credits_used' => MoneyMath::floorToScale($used, 2),
            'trade_in_value' => $value,
            'trade_in_applied' => $applied,
            'leftover_to_deposit' => MoneyMath::floorToScale($value - $applied, 2),
            'shortfall' => MoneyMath::floorToScale($price - $applied, 2),
        ];
    }

    /**
     * Eksekusi di dalam transaksi pemanggil (Student & users sudah dikunci):
     * potong credit asal, catat saldo Deposit, beri credit paket tujuan.
     *
     * @return array{purchase: CoursePackagePurchase, trade_in_debit: CourseCredit, trade_in_applied: float}
     */
    private function settle(string $userId, Student $lockedStudent, CoursePackagePurchase $source, CoursePackage $target, array $plan, float $depositPortion, float $gatewayPortion, string $orderId): array
    {
        $label = ($plan['mode'] === 'convert' ? 'Convert ke ' : 'Upgrade ke ').$target->name;

        try {
            $tradeInDebit = $this->debitService->debitPurchase($lockedStudent, $source, $plan['credits_used'], CourseCredit::SOURCE_TRADE_IN_DEBIT, "Trade-in credit untuk {$label}");
        } catch (InsufficientCourseCreditException) {
            throw new UpgradeConfirmationFailedException('insufficient_trade_in');
        }

        if (! $tradeInDebit) {
            throw new UpgradeConfirmationFailedException('insufficient_trade_in');
        }

        $value = MoneyMath::floorToScale((float) $tradeInDebit->allocations->sum('value'), 2);
        $applied = MoneyMath::floorToScale(min($value, $plan['price']), 2);
        $leftover = MoneyMath::floorToScale($value - $applied, 2);

        // Jaring pengaman: trade-in + saldo + gateway harus menutup harga penuh.
        if ($applied + $depositPortion + $gatewayPortion + 0.000001 < $plan['price']) {
            throw new UpgradeConfirmationFailedException('insufficient_trade_in');
        }

        if ($depositPortion > 0.0 || $leftover > 0.0) {
            $this->recordDeposit($userId, $depositPortion, $leftover, $label, $orderId);
        }

        $purchase = CoursePackagePurchase::create([
            'student_id' => $lockedStudent->id,
            'course_package_id' => $target->id,
            'price_paid' => $plan['price'],
            'credits_granted' => $plan['quantity'],
            'source' => $plan['mode'] === 'convert' ? CoursePackagePurchase::SOURCE_CONVERT_PURCHASE : CoursePackagePurchase::SOURCE_UPGRADE_PURCHASE,
            'status' => CoursePackagePurchase::STATUS_COMPLETED,
        ]);

        CourseCredit::create([
            'student_id' => $lockedStudent->id,
            'course_package_purchase_id' => $purchase->id,
            'source_type' => CourseCredit::SOURCE_PURCHASE,
            'debit' => 0,
            'kredit' => $plan['quantity'],
            // Dari baris debit barusan (bukan query "baris terakhir" -- keduanya
            // bisa ber-created_at sama persis dalam 1 detik).
            'balance' => MoneyMath::floorToScale((float) $tradeInDebit->balance + $plan['quantity'], 2),
            'description' => $label,
        ]);

        return ['purchase' => $purchase, 'trade_in_debit' => $tradeInDebit, 'trade_in_applied' => $applied];
    }

    /** Saldo Deposit: dipotong $debit (porsi saldo) dan/atau ditambah $kredit (kelebihan trade-in). */
    private function recordDeposit(string $userId, float $debit, float $kredit, string $label, string $orderId): void
    {
        $lastDeposit = Deposit::where('user_id', $userId)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first();
        $before = (float) ($lastDeposit?->balance ?? 0);

        if ($before + 0.000001 < $debit) {
            throw new UpgradeConfirmationFailedException('insufficient_deposit');
        }

        $after = MoneyMath::floorToScale($before - $debit + $kredit, 2);

        Deposit::create([
            'user_id' => $userId,
            'debit' => $debit,
            'kredit' => $kredit,
            'balance' => $after,
            'description' => "{$label} (order {$orderId})",
            'payment_status' => 'success',
            'payment_method' => $debit > 0 ? 'saldo' : 'trade_in_credit',
            'payment_date' => now(),
        ]);

        // Satu pesanan hanya salah satu: kurang (dipotong saldo) atau lebih (masuk saldo).
        Transaction::create([
            'transaction_code' => 'TRX-'.now()->format('YmdHisv').'-'.strtoupper(Str::random(6)),
            'user_id' => $userId,
            'type' => $debit > 0 ? 'debit' : 'credit',
            'amount' => $debit > 0 ? $debit : $kredit,
            'balance_before' => $before,
            'balance_after' => $after,
            'description' => $debit > 0 ? "{$label} (porsi saldo)" : "{$label} (kelebihan trade-in masuk saldo)",
            'reference_type' => 'course_package_payment',
            'reference_id' => $orderId,
            'status' => 'success',
            'channel' => $debit > 0 ? 'saldo' : 'trade_in_credit',
            'metadata' => ['order_id' => $orderId, 'source' => 'inayule.upgrade'],
            'created_by' => $userId,
            'transaction_date' => now(),
        ]);
    }

    /** Sisa credit 1 baris pembelian. */
    public function remaining(CoursePackagePurchase $source): float
    {
        return $this->debitService->remainingByPurchase([$source])[$source->id] ?? 0.0;
    }
}
