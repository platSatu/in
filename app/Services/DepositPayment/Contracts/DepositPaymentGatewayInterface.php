<?php

namespace App\Services\DepositPayment\Contracts;

use App\Models\DepositPayment;
use Illuminate\Http\Request;

/**
 * Kontrak khusus alur topup saldo (dipenuhi oleh DepositMidtransGateway,
 * DepositDuitkuGateway, DepositIpaymuGateway). SENGAJA dibuat TERPISAH dari
 * App\Services\Payment\Contracts\PaymentGatewayInterface (yang dipakai
 * FormPaymentController/form_payments) -- keputusan owner supaya fitur
 * topup saldo ini tidak menyentuh sama sekali alur pembayaran Form/Quiz
 * yang sudah live di produksi. Isi method-nya sengaja mirip (biar konsisten
 * cara mikirnya), tapi jalur kodenya independen 100%.
 */
interface DepositPaymentGatewayInterface
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
    public function getPaymentMethods(DepositPayment $payment): array;

    /**
     * Buat transaksi ke gateway. $paymentMethod hanya dipakai gateway yang
     * requiresMethodSelection() = true.
     *
     * @return array{redirect_url: ?string, reference: ?string, raw: array}
     */
    public function createTransaction(DepositPayment $payment, ?string $paymentMethod = null): array;

    /**
     * Baca & verifikasi notifikasi/callback dari gateway. Melempar
     * App\Services\Payment\PaymentSignatureMismatchException kalau
     * signature tidak valid.
     *
     * @return array{order_id: string, is_paid: bool, is_failed: bool, reference: ?string, raw: array}
     */
    public function handleCallback(Request $request): array;
}
