<?php

namespace App\Services\CoursePackagePayment\Contracts;

use App\Models\CoursePackagePayment;
use Illuminate\Http\Request;

/**
 * Kontrak khusus alur checkout pembelian package (dipenuhi oleh
 * CoursePackageMidtransGateway, CoursePackageDuitkuGateway,
 * CoursePackageIpaymuGateway). SENGAJA dibuat TERPISAH dari
 * App\Services\Payment\Contracts\PaymentGatewayInterface (form_payments) DAN
 * dari App\Services\DepositPayment\Contracts\DepositPaymentGatewayInterface
 * (topup saldo) -- keputusan konsisten dengan yang sudah dipakai 2x
 * sebelumnya di project ini: supaya fitur baru ini tidak menyentuh sama
 * sekali alur pembayaran Form/Quiz atau topup saldo yang sudah live. Isi
 * method-nya sengaja mirip (biar konsisten cara mikirnya), tapi jalur
 * kodenya independen 100%.
 *
 * Beda penting dari DepositPaymentGatewayInterface: amount yang ditagih ke
 * gateway di sini adalah `gateway_portion` (SISA setelah dipotong saldo
 * Deposit yang otomatis dipakai dulu), BUKAN harga package penuh -- lihat
 * docblock InaYulePackageCheckoutController.
 */
interface CoursePackagePaymentGatewayInterface
{
    /**
     * True kalau gateway ini butuh user memilih metode pembayaran dulu
     * (mis. Duitku) sebelum transaksi bisa dibuat.
     */
    public function requiresMethodSelection(): bool;

    /**
     * Daftar metode pembayaran yang tersedia untuk transaksi ini. Kosong
     * untuk gateway yang punya halaman checkout sendiri (Midtrans/iPaymu).
     *
     * @return array<int, array{code: string, name: string, image: ?string, fee: mixed}>
     */
    public function getPaymentMethods(CoursePackagePayment $payment): array;

    /**
     * Buat transaksi ke gateway sebesar $payment->gateway_portion (BUKAN
     * price_total). $paymentMethod hanya dipakai gateway yang
     * requiresMethodSelection() = true.
     *
     * @return array{redirect_url: ?string, reference: ?string, raw: array}
     */
    public function createTransaction(CoursePackagePayment $payment, ?string $paymentMethod = null): array;

    /**
     * Baca & verifikasi notifikasi/callback dari gateway. Melempar
     * App\Services\Payment\PaymentSignatureMismatchException kalau
     * signature tidak valid.
     *
     * @return array{order_id: string, is_paid: bool, is_failed: bool, reference: ?string, raw: array}
     */
    public function handleCallback(Request $request): array;
}
