<?php

namespace App\Services\Referral;

use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\ReferralRedemption;
use App\Models\ReferralSetting;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kode Referal INA YULE (25 November 2026). Murid B membagikan kodenya, murid baru A memakainya saat checkout:
 *  - A dapat diskon X% di pembelian pertamanya (setelah potongan voucher, kalau ada).
 *  - B dapat komisi Y% dari yang BENAR-BENAR dibayar A, masuk ke Saldo setelah pembayaran A lunas.
 *    Saldo hanya bisa dipakai membeli paket, tidak bisa dicairkan.
 *
 * Penjagaan (semua di server, angka dari browser tidak pernah dipakai):
 *  1. Program aktif & kode milik akun lain (bukan diri sendiri, bukan email/HP yang sama).
 *  2. A murid baru: belum pernah punya paket INA YULE (selain trial gratis) dan belum pernah memakai kode referal.
 *  3. Kode B boleh dipakai banyak murid baru; B dapat komisi 1x dari tiap pembeli (karena tiap pembeli hanya bisa 1x).
 *  4. reserve() mengecek ulang di dalam lock akun pembeli, jadi klik ganda / 2 tab tidak bisa memakai kode 2x.
 *  5. payCommission() mengunci baris pemakaian & hanya membayar sekali (idempoten), apa pun jalur pembayarannya.
 */
class ReferralService
{
    public const SALDO_METHOD = 'referral_bonus';

    /** @return array{ok: bool, message: string, discount?: float, final?: float, referrer?: User, setting?: ReferralSetting} */
    public function preview(?string $code, User $buyer, Student $student, float $price): array
    {
        return $this->evaluate($code, $buyer, $student, $price);
    }

    /**
     * Pakai kode: dicek ulang di dalam lock akun pembeli lalu dicatat (belum tertaut pembayaran).
     * Panggil attach() setelah pembayaran dibuat, atau release() kalau checkout batal.
     */
    public function reserve(?string $code, User $buyer, Student $student, CoursePackage $package, float $price): array
    {
        return DB::transaction(function () use ($code, $buyer, $student, $package, $price) {
            DB::table('users')->where('id', $buyer->id)->lockForUpdate()->first();

            $result = $this->evaluate($code, $buyer, $student, $price);
            if (! $result['ok']) {
                return $result;
            }

            $result['redemption'] = ReferralRedemption::create([
                'code' => $result['referrer']->referral_code,
                'referrer_user_id' => $result['referrer']->id,
                'buyer_user_id' => $buyer->id,
                'buyer_student_id' => $student->id,
                'course_package_id' => $package->id,
                'price_before' => $price,
                'buyer_discount_percent' => $result['setting']->buyer_discount_percent,
                'discount_amount' => $result['discount'],
                'price_after' => $result['final'],
                'referrer_commission_percent' => $result['setting']->referrer_commission_percent,
            ]);

            return $result;
        });
    }

    public function attach(ReferralRedemption $redemption, string $paymentId): void
    {
        $redemption->update(['course_package_payment_id' => $paymentId]);
    }

    public function release(ReferralRedemption $redemption): void
    {
        if (! $redemption->course_package_payment_id) {
            $redemption->delete();
        }
    }

    /**
     * Pembayaran lunas -> komisi pemberi kode masuk ke Saldo. Aman dipanggil berkali-kali (hanya dibayar sekali).
     * Gagal di sini tidak membatalkan pembelian pembeli; dicatat di log untuk dicek admin.
     */
    public function payCommission(CoursePackagePayment $payment): void
    {
        try {
            DB::transaction(function () use ($payment) {
                $redemption = ReferralRedemption::where('course_package_payment_id', $payment->id)->lockForUpdate()->first();
                $payment = CoursePackagePayment::find($payment->id);

                if (! $redemption || $redemption->commission_paid_at || ! $payment || ! $payment->isPaid()) {
                    return;
                }

                // Dari yang benar-benar dibayar pembeli (saldo + gateway), dibulatkan ke bawah per rupiah.
                $paid = (float) $payment->price_total;
                $commission = floor($paid * (float) $redemption->referrer_commission_percent / 100);

                if ($commission <= 0) {
                    $redemption->update(['commission_amount' => 0, 'commission_paid_at' => now()]);

                    return;
                }

                DB::table('users')->where('id', $redemption->referrer_user_id)->lockForUpdate()->first();
                $before = Deposit::currentBalanceFor($redemption->referrer_user_id);
                $buyerName = $redemption->buyer?->name ?? 'murid baru';
                $packageName = $redemption->coursePackage?->name ?? 'paket';

                $deposit = Deposit::create([
                    'user_id' => $redemption->referrer_user_id,
                    'debit' => 0,
                    'kredit' => $commission,
                    'balance' => $before + $commission,
                    'description' => "Komisi referal: {$buyerName} membeli {$packageName} (hanya untuk beli paket, tidak bisa dicairkan)",
                    'payment_status' => 'success',
                    'payment_method' => self::SALDO_METHOD,
                    'payment_date' => now(),
                ]);

                Transaction::create([
                    'transaction_code' => 'TRX-'.now()->format('YmdHisv').'-'.strtoupper(Str::random(6)),
                    'user_id' => $redemption->referrer_user_id,
                    'type' => 'credit',
                    'amount' => $commission,
                    'balance_before' => $before,
                    'balance_after' => $before + $commission,
                    'description' => 'Komisi referal dari '.$buyerName,
                    'reference_type' => 'referral_redemption',
                    'reference_id' => $redemption->id,
                    'status' => 'success',
                    'channel' => self::SALDO_METHOD,
                    'metadata' => ['order_id' => $payment->order_id, 'buyer_user_id' => $redemption->buyer_user_id],
                    'created_by' => $redemption->referrer_user_id,
                    'transaction_date' => now(),
                ]);

                $redemption->update([
                    'commission_amount' => $commission,
                    'commission_deposit_id' => $deposit->id,
                    'commission_paid_at' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('[REFERRAL] Komisi gagal dibayar, cek manual', ['payment_id' => $payment->id, 'message' => $e->getMessage()]);
        }
    }

    /** Murid ini masih boleh memakai kode referal (belum pernah beli paket & belum pernah pakai referal)? */
    public function isNewStudent(User $buyer, Student $student): bool
    {
        $hasPackage = CoursePackagePurchase::where('student_id', $student->id)
            ->where('source', '!=', CoursePackagePurchase::SOURCE_TRIAL_CLAIM)
            ->exists();

        $usedReferral = ReferralRedemption::where(fn ($q) => $q->where('buyer_user_id', $buyer->id)->orWhere('buyer_student_id', $student->id))
            ->tap(fn ($q) => ReferralRedemption::applyActive($q))
            ->exists();

        return ! $hasPackage && ! $usedReferral;
    }

    /** @return array{ok: bool, message: string, discount?: float, final?: float, referrer?: User, setting?: ReferralSetting} */
    private function evaluate(?string $code, User $buyer, Student $student, float $price): array
    {
        $fail = fn (string $message) => ['ok' => false, 'message' => $message];
        $setting = ReferralSetting::current();
        $code = strtoupper(preg_replace('/\s+/', '', (string) $code));

        if (! $setting->is_active) {
            return $fail('Program kode referal sedang tidak aktif.');
        }

        $referrer = $code !== '' ? User::where('referral_code', $code)->first() : null;
        if (! $referrer) {
            return $fail('Kode referal tidak ditemukan. Periksa lagi penulisannya.');
        }
        if ($referrer->id === $buyer->id || $this->samePerson($referrer, $buyer)) {
            return $fail('Kode referal milik sendiri tidak bisa dipakai.');
        }
        if (! $this->isNewStudent($buyer, $student)) {
            return $fail('Kode referal hanya untuk pembelian pertama murid baru INA YULE.');
        }

        $discount = floor($price * (float) $setting->buyer_discount_percent / 100);
        if ($discount <= 0 || $discount >= $price) {
            return $fail('Kode referal tidak bisa dipakai untuk harga ini.');
        }

        return [
            'ok' => true,
            'message' => 'Kode referal '.$referrer->referral_code.' dipakai: hemat Rp '.number_format($discount, 0, ',', '.').'.',
            'discount' => $discount,
            'final' => $price - $discount,
            'referrer' => $referrer,
            'setting' => $setting,
        ];
    }

    /** Akun lain milik orang yang sama (email atau no. HP sama) tidak boleh saling memberi komisi. */
    private function samePerson(User $referrer, User $buyer): bool
    {
        $sameEmail = strcasecmp(trim((string) $referrer->email), trim((string) $buyer->email)) === 0;
        $phone = fn ($value) => preg_replace('/^(62|0)/', '', preg_replace('/\D/', '', (string) $value));
        $samePhone = $phone($referrer->handphone) !== '' && $phone($referrer->handphone) === $phone($buyer->handphone);

        return $sameEmail || $samePhone;
    }
}
