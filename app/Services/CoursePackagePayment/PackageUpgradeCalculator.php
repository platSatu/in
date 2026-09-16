<?php

namespace App\Services\CoursePackagePayment;

use App\Helpers\MoneyMath;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Student;
use App\Services\CourseCredit\CourseCreditDebitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FASE 4 "Konversi/Upgrade Paket" (16 September 2026, bagian 1 -- lihat
 * catatan scope di bawah) -- satu-satunya pintu untuk menghitung & (untuk
 * jalur instan) mengeksekusi upgrade package seorang student, dengan urutan
 * penutupan harga: TRADE-IN saldo credit lama dulu, baru sisanya lewat
 * saldo Deposit, baru sisanya lagi lewat payment gateway -- pola waterfall
 * ini persis kesepakatan diskusi:
 *
 *   "dihitung dulu sisa creditnya dan dihitung ke rupiah, jika kurang mau
 *    sedapatnya saja atau lanjut ke payment gateway, jika kelebihan akan
 *    masuk ke saldo (tidak bisa dicairkan tapi tetap dipergunakan lagi
 *    untuk membeli paket)"
 *
 * SCOPE bagian 1 (yang dibangun sekarang): preview() (breakdown angka,
 * TANPA menyentuh DB apa pun) & completeInstant() (jalur SELESAI SEKARANG
 * JUGA -- trade-in + Deposit sudah cukup menutup 100% harga package baru,
 * TIDAK butuh payment gateway sama sekali).
 *
 * SENGAJA BELUM dibangun di bagian ini (menyusul, sama seperti Fase 2
 * dipecah jadi backend dulu baru controller/UI): jalur GATEWAY (kalau
 * trade-in + Deposit masih kurang) beserta perluasan
 * InaYulePackageWebhookController untuk memprosesnya, dan controller/route/
 * view yang benar-benar memanggil class ini dari halaman student portal --
 * bagian gateway sengaja ditunda karena butuh pengujian end-to-end dengan
 * gateway sungguhan yang tidak bisa dilakukan dari sandbox ini.
 */
class PackageUpgradeCalculator
{
    public function __construct(
        private readonly CourseCreditDebitService $debitService = new CourseCreditDebitService()
    ) {
    }

    /**
     * Breakdown harga upgrade TANPA menyentuh DB apa pun (baca-saja) --
     * dipakai untuk menampilkan preview ke student SEBELUM dia konfirmasi,
     * dan untuk MEMILIH jalur mana yang dipakai (instan vs gateway), pola
     * "preview di luar lock, cek ulang di dalam lock" sama seperti
     * InaYulePackageCheckoutController::store(). Angka di sini BISA berubah
     * sampai commit sungguhan -- completeInstant() SELALU menghitung ulang
     * semuanya dari 0 di dalam transaction-nya sendiri, tidak percaya hasil
     * preview ini.
     *
     * @return array{
     *     target_price: float,
     *     credit_balance: float,
     *     credit_trade_in_value: float,
     *     trade_in_applied_to_price: float,
     *     trade_in_leftover_to_deposit: float,
     *     remaining_price_after_trade_in: float,
     *     deposit_balance: float,
     *     deposit_portion: float,
     *     gateway_portion: float,
     * }
     */
    public function preview(mixed $user, Student $student, CoursePackage $targetPackage): array
    {
        $price = $targetPackage->effectivePrice();

        $tradeIn = $this->debitService->previewTradeInValue($student);

        $tradeInApplied = MoneyMath::floorToScale(min($tradeIn['value'], $price), 2);
        $tradeInLeftoverToDeposit = MoneyMath::floorToScale($tradeIn['value'] - $tradeInApplied, 2);
        $remainingAfterTradeIn = MoneyMath::floorToScale($price - $tradeInApplied, 2);

        $depositBalance = Deposit::currentBalanceFor((string) $user->id);
        $depositPortion = MoneyMath::floorToScale(min($depositBalance, $remainingAfterTradeIn), 2);
        $gatewayPortion = MoneyMath::floorToScale(max($remainingAfterTradeIn - $depositPortion, 0.0), 2);

        return [
            'target_price' => $price,
            'credit_balance' => $tradeIn['balance'],
            'credit_trade_in_value' => $tradeIn['value'],
            'trade_in_applied_to_price' => $tradeInApplied,
            'trade_in_leftover_to_deposit' => $tradeInLeftoverToDeposit,
            'remaining_price_after_trade_in' => $remainingAfterTradeIn,
            'deposit_balance' => $depositBalance,
            'deposit_portion' => $depositPortion,
            'gateway_portion' => $gatewayPortion,
        ];
    }

    /**
     * Jalur INSTAN -- dipakai HANYA kalau preview() menunjukkan
     * gateway_portion <= 0 (trade-in + saldo Deposit sudah cukup menutup
     * 100% harga package baru). SEMUA di dalam 1 DB transaction, urutan
     * lock SAMA seperti InaYulePackageCheckoutController::
     * completeWithDepositOnly() (Student, lalu users, lalu Deposit
     * terakhir) supaya tidak ada risiko deadlock beda urutan lock antara 2
     * alur checkout yang mirip ini.
     *
     * Re-cek SEMUA angka dari 0 di dalam lock (trade-in lewat
     * CourseCreditDebitService::debitEntireBalance() yang lock sendiri
     * baris CourseCredit terakhir, lalu Deposit terakhir) -- TIDAK percaya
     * hasil preview() yang dipanggil di luar transaction ini. Kalau
     * ternyata setelah dicek ulang saldo Deposit tidak lagi cukup (race,
     * atau trade-in ternyata lebih kecil dari sangkaan preview), SELURUH
     * transaction dibatalkan (termasuk trade-in debit-nya) -- baris
     * gagal ini tidak meninggalkan efek apa pun.
     *
     * @return array{ok: bool, payment?: CoursePackagePayment}
     */
    public function completeInstant(mixed $user, Student $student, CoursePackage $targetPackage): array
    {
        return DB::transaction(function () use ($user, $student, $targetPackage): array {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();

            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();

            $price = $targetPackage->effectivePrice();

            // Trade-in dieksekusi lebih dulu -- debitEntireBalance() lock
            // sendiri baris CourseCredit terakhir DI DALAM transaction yang
            // sama (savepoint, connection yang sama, tidak mengunci diri
            // sendiri).
            $tradeInDebit = $this->debitService->debitEntireBalance(
                $lockedStudent,
                CourseCredit::SOURCE_TRADE_IN_DEBIT,
                'Trade-in saldo credit lama untuk upgrade ke package: ' . $targetPackage->name
            );

            $tradeInValue = $tradeInDebit
                ? MoneyMath::floorToScale((float) $tradeInDebit->allocations->sum(fn ($allocation) => (float) $allocation->value), 2)
                : 0.0;

            $tradeInApplied = MoneyMath::floorToScale(min($tradeInValue, $price), 2);
            $tradeInLeftover = MoneyMath::floorToScale($tradeInValue - $tradeInApplied, 2);
            $remainingAfterTradeIn = MoneyMath::floorToScale($price - $tradeInApplied, 2);

            $lastDeposit = Deposit::where('user_id', $user->id)
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $depositBalanceBefore = (float) ($lastDeposit?->balance ?? 0);

            // Re-cek DI DALAM lock -- kalau ternyata tidak cukup lagi
            // (race, atau trade-in ternyata lebih kecil dari sangkaan
            // preview), batalkan SELURUH transaction (trade-in debit di
            // atas ikut di-rollback otomatis).
            if ($depositBalanceBefore < $remainingAfterTradeIn) {
                return ['ok' => false];
            }

            // Leftover trade-in (kalau nilai credit lama LEBIH BESAR dari
            // harga package baru) masuk ke saldo Deposit -- BUKAN dicairkan
            // tunai, sesuai kesepakatan diskusi ("tidak hangus tapi harus
            // dipergunakan untuk membeli paket lagi").
            $depositBalanceAfter = MoneyMath::floorToScale(
                $depositBalanceBefore - $remainingAfterTradeIn + $tradeInLeftover,
                2
            );

            $orderId = $this->generateOrderId();

            if ($remainingAfterTradeIn > 0.0 || $tradeInLeftover > 0.0) {
                Deposit::create([
                    'user_id' => (string) $user->id,
                    'debit' => $remainingAfterTradeIn,
                    'kredit' => $tradeInLeftover,
                    'balance' => $depositBalanceAfter,
                    'description' => "Upgrade package: {$targetPackage->name} (order {$orderId})",
                    'payment_status' => 'success',
                    'payment_method' => $remainingAfterTradeIn > 0.0 ? 'saldo' : 'trade_in_credit',
                    'payment_date' => now(),
                ]);
            }

            $purchase = CoursePackagePurchase::create([
                'student_id' => $lockedStudent->id,
                'course_package_id' => $targetPackage->id,
                'price_paid' => $price,
                'credits_granted' => $targetPackage->credits,
                'source' => CoursePackagePurchase::SOURCE_UPGRADE_PURCHASE,
                'status' => CoursePackagePurchase::STATUS_COMPLETED,
            ]);

            $newCreditBalance = MoneyMath::floorToScale(
                CourseCredit::currentBalanceFor($lockedStudent->id) + (float) $targetPackage->credits,
                2
            );

            CourseCredit::create([
                'student_id' => $lockedStudent->id,
                'course_package_purchase_id' => $purchase->id,
                'source_type' => CourseCredit::SOURCE_PURCHASE,
                'debit' => 0,
                'kredit' => $targetPackage->credits,
                'balance' => $newCreditBalance,
                'description' => 'Upgrade package: ' . $targetPackage->name,
            ]);

            $payment = CoursePackagePayment::create([
                'student_id' => $lockedStudent->id,
                'user_id' => (string) $user->id,
                'course_package_id' => $targetPackage->id,
                'course_package_purchase_id' => $purchase->id,
                'order_id' => $orderId,
                'gateway' => null,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'handphone' => $user->handphone,
                'price_total' => $price,
                'deposit_portion' => $remainingAfterTradeIn,
                'gateway_portion' => 0,
                'credit_trade_in_portion' => $tradeInApplied,
                'trade_in_course_credit_id' => $tradeInDebit?->id,
                'credits_granted' => $targetPackage->credits,
                'status' => CoursePackagePayment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            return ['ok' => true, 'payment' => $payment];
        });
    }

    private function generateOrderId(): string
    {
        do {
            $orderId = 'CPPU' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (CoursePackagePayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
