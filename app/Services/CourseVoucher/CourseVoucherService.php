<?php

namespace App\Services\CourseVoucher;

use App\Models\CoursePackage;
use App\Models\CourseVoucher;
use App\Models\CourseVoucherRedemption;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Aturan voucher paket credit InaYule (20 November 2026). Dipakai checkout paket baru
 * (InaYulePackageCheckoutController). Harga & potongan SELALU dihitung ulang di server.
 *
 * Syarat kode bisa dipakai:
 * 1. Voucher aktif & tanggal hari ini dalam masa berlaku.
 * 2. Berlaku untuk paket yang dibeli (kosong = semua paket).
 * 3. Kalau voucher "khusus murid yang diizinkan": switch "Bisa pakai voucher" murid ini ON.
 * 4. Kuota masih ada (dihitung dari pemakaian yang masih berlaku).
 * 5. Akun ini belum pernah memakai kode yang sama (1 akun 1x per kode).
 * 6. Potongan lebih kecil dari harga paket (harga akhir tidak boleh Rp 0).
 *
 * reserve() menjalankan pengecekan 4 & 5 di dalam lock baris voucher, jadi klik ganda / 2 tab / banyak murid
 * sekaligus tidak bisa melewati kuota maupun memakai kode yang sama 2x.
 */
class CourseVoucherService
{
    /**
     * Cek kode tanpa menyimpan apa pun (untuk tampilan checkout).
     *
     * @return array{ok: bool, message: string, voucher?: CourseVoucher, discount?: float, final?: float}
     */
    public function preview(?string $code, string $userId, Student $student, CoursePackage $package, float $price): array
    {
        $voucher = CourseVoucher::where('code', CourseVoucher::normalizeCode($code))->first();

        return $this->evaluate($voucher, $userId, $student, $package, $price);
    }

    /**
     * Pakai kode: dicek ulang di dalam lock lalu dicatat (belum tertaut pembayaran). Panggil attach() setelah
     * pembayaran dibuat, atau release() kalau checkout batal sebelum pembayaran dibuat.
     *
     * @return array{ok: bool, message: string, voucher?: CourseVoucher, discount?: float, final?: float, redemption?: CourseVoucherRedemption}
     */
    public function reserve(?string $code, string $userId, Student $student, CoursePackage $package, float $price): array
    {
        return DB::transaction(function () use ($code, $userId, $student, $package, $price) {
            $voucher = CourseVoucher::where('code', CourseVoucher::normalizeCode($code))->lockForUpdate()->first();
            $result = $this->evaluate($voucher, $userId, $student, $package, $price);

            if (! $result['ok']) {
                return $result;
            }

            $result['redemption'] = CourseVoucherRedemption::create([
                'course_voucher_id' => $voucher->id,
                'user_id' => $userId,
                'student_id' => $student->id,
                'course_package_id' => $package->id,
                'price_before' => $price,
                'discount_amount' => $result['discount'],
                'price_after' => $result['final'],
            ]);

            return $result;
        });
    }

    public function attach(CourseVoucherRedemption $redemption, string $paymentId): void
    {
        $redemption->update(['course_package_payment_id' => $paymentId]);
    }

    /** Checkout batal sebelum ada pembayaran: kuota langsung dikembalikan. */
    public function release(CourseVoucherRedemption $redemption): void
    {
        if (! $redemption->course_package_payment_id) {
            $redemption->delete();
        }
    }

    /** @return array{ok: bool, message: string, voucher?: CourseVoucher, discount?: float, final?: float} */
    private function evaluate(?CourseVoucher $voucher, string $userId, Student $student, CoursePackage $package, float $price): array
    {
        $fail = fn (string $message) => ['ok' => false, 'message' => $message];

        if (! $voucher || $voucher->status !== CourseVoucher::STATUS_ACTIVE) {
            return $fail('Kode voucher tidak ditemukan atau sudah tidak aktif.');
        }
        if (now()->lt($voucher->valid_from)) {
            return $fail('Voucher ini baru bisa dipakai mulai '.$voucher->valid_from->translatedFormat('d M Y').'.');
        }
        if (now()->gt($voucher->valid_until)) {
            return $fail('Masa berlaku voucher ini sudah habis.');
        }
        if (! $voucher->appliesToPackage($package->id)) {
            return $fail('Voucher ini tidak berlaku untuk paket yang Anda pilih.');
        }
        if ($voucher->only_eligible_students && ! $student->voucher_eligible) {
            return $fail('Akun Anda belum terdaftar untuk memakai voucher ini. Silakan hubungi admin.');
        }
        if (CourseVoucherRedemption::applyActive($voucher->redemptions()->getQuery())->where('user_id', $userId)->exists()) {
            return $fail('Anda sudah pernah memakai kode voucher ini.');
        }
        if ($voucher->activeRedemptionCount() >= $voucher->quota) {
            return $fail('Kuota voucher ini sudah habis.');
        }

        $discount = $voucher->discountFor($price);
        if ($discount <= 0 || $discount >= $price) {
            return $fail('Voucher ini tidak bisa dipakai untuk harga paket ini.');
        }

        return [
            'ok' => true,
            'message' => "Voucher {$voucher->code} dipakai: hemat Rp ".number_format($discount, 0, ',', '.').'.',
            'voucher' => $voucher,
            'discount' => $discount,
            'final' => $price - $discount,
        ];
    }
}
