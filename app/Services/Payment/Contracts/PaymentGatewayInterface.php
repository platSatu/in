<?php

namespace App\Services\Payment\Contracts;

use Illuminate\Http\Request;

/**
 * Kontrak yang sama dipenuhi oleh MidtransGateway, DuitkuGateway, dan
 * IpaymuGateway supaya FormPaymentController tidak perlu tahu detail
 * masing-masing gateway (semuanya dipanggil lewat interface ini).
 *
 * FIX (10 September 2026): type-hint diganti dari FormPayment (konkret)
 * jadi Payable (interface) supaya gateway yang sama bisa dipakai ulang oleh
 * ApplicationPayment (fitur Apply Kampus) -- lihat docblock lengkap di
 * App\Services\Payment\Contracts\Payable. Nilai yang dibaca gateway class
 * (order id, amount, dst) SAMA PERSIS seperti sebelumnya untuk FormPayment,
 * cuma dipanggil lewat method interface Payable, bukan properti langsung.
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
    public function getPaymentMethods(Payable $payment): array;

    /**
     * Buat transaksi ke gateway. $paymentMethod hanya dipakai gateway yang
     * requiresMethodSelection() = true.
     *
     * @return array{redirect_url: ?string, reference: ?string, raw: array}
     */
    public function createTransaction(Payable $payment, ?string $paymentMethod = null): array;

    /**
     * Baca & verifikasi notifikasi/callback dari gateway. Melempar
     * PaymentSignatureMismatchException kalau signature tidak valid.
     *
     * @return array{order_id: string, is_paid: bool, is_failed: bool, reference: ?string, raw: array}
     */
    public function handleCallback(Request $request): array;
}
