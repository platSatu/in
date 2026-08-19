<?php

namespace App\Services\Payment\Contracts;

use App\Models\FormPayment;
use Illuminate\Http\Request;

/**
 * Kontrak yang sama dipenuhi oleh MidtransGateway, DuitkuGateway, dan
 * IpaymuGateway supaya FormPaymentController tidak perlu tahu detail
 * masing-masing gateway (semuanya dipanggil lewat interface ini).
 */
interface PaymentGatewayInterface
{
    /**
     * True kalau gateway ini butuh user memilih metode pembayaran dulu
     * (mis. Duitku) sebelum transaksi bisa dibuat. Kalau true, controller
     * akan panggil getPaymentMethods() dulu, baru createTransaction()
     * dipanggil ulang setelah user memilih salah satu metode.
     */
    public function requiresMethodSelection(): bool;

    /**
     * Daftar metode pembayaran yang tersedia untuk transaksi ini.
     * Kosong untuk gateway yang punya halaman checkout sendiri (Midtrans/iPaymu).
     *
     * @return array<int, array{code: string, name: string, image: ?string, fee: mixed}>
     */
    public function getPaymentMethods(FormPayment $payment): array;

    /**
     * Buat transaksi ke gateway. $paymentMethod hanya dipakai gateway yang
     * requiresMethodSelection() = true.
     *
     * @return array{redirect_url: ?string, reference: ?string, raw: array}
     */
    public function createTransaction(FormPayment $payment, ?string $paymentMethod = null): array;

    /**
     * Baca & verifikasi notifikasi/callback dari gateway. Melempar
     * PaymentSignatureMismatchException kalau signature tidak valid.
     *
     * @return array{order_id: string, is_paid: bool, is_failed: bool, reference: ?string, raw: array}
     */
    public function handleCallback(Request $request): array;
}
