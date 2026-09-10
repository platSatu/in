<?php

namespace App\Services\Payment\Contracts;

/**
 * FIX (10 September 2026): fitur "Alur Pembayaran 2 Arah Apply Kampus".
 *
 * Sebelumnya DuitkuGateway/MidtransGateway/IpaymuGateway & PaymentGatewayInterface
 * type-hint LANGSUNG ke App\Models\FormPayment (dipakai Quiz Form) -- artinya
 * gateway yang sama tidak bisa dipakai ulang untuk ApplicationPayment (Apply
 * Kampus) tanpa interface generik ini.
 *
 * Kenapa perlu di-generic-kan (bukan bikin gateway terpisah/duplikat)? Karena
 * webhook Midtrans TIDAK mendukung notification-url per-transaksi (beda dari
 * Duitku/iPaymu yang callbackUrl/notifyUrl-nya dikirim per-request) -- URL
 * webhook Midtrans wajib SATU alamat yang sama, dikonfigurasi sekali di
 * dashboard Midtrans. Jadi webhook HARUS dipakai bersama antara FormPayment &
 * ApplicationPayment, dan cara paling aman melakukan itu adalah lewat
 * interface generik ini -- BUKAN bikin controller/gateway kedua yang
 * berpotensi keliru alamat webhook-nya.
 *
 * FormPayment & ApplicationPayment SAMA-SAMA implements Payable ini. Semua
 * method di bawah cuma MEMBUNGKUS field yang sudah ada di masing-masing model
 * (lihat implementasinya di FormPayment::getPayerName() dkk) -- tidak ada
 * satu pun perilaku FormPayment yang berubah, cuma dipanggil lewat nama
 * method interface ini alih-alih akses properti langsung.
 */
interface Payable
{
    public function getOrderId(): string;

    public function getAmount(): int;

    public function getPayerName(): string;

    public function getPayerEmail(): string;

    public function getPayerPhone(): string;

    /**
     * Dipakai sebagai nama produk/deskripsi transaksi di Midtrans & iPaymu
     * (dulu "Pembayaran " . $payment->form->name).
     */
    public function getDescription(): string;

    /**
     * Dipakai sebagai returnUrl/cancelUrl Duitku & iPaymu (dulu hardcode
     * route('frontend.payment.return', ...) langsung di gateway class).
     */
    public function getReturnUrl(): string;
}
