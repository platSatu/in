<?php

namespace App\Http\Controllers;

use App\Models\ApplicationPayment;
use Illuminate\View\View;

/**
 * FASE 6 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- halaman
 * invoice PUBLIK (TANPA login) dari link yang dikirim via WhatsApp begitu
 * ApplicationPayment (Registration Fee ATAU Departure Fee) dikonfirmasi
 * "paid" oleh webhook -- lihat
 * Payment\FormPaymentController::sendInvoiceNotification().
 *
 * SENGAJA tanpa middleware 'auth': siswa membuka link ini langsung dari chat
 * WhatsApp, seringkali dari device/browser lain yang belum tentu sedang
 * login ke akun InaStudy-nya. Keamanannya BUKAN dari login, tapi dari
 * 'invoice_token' itu sendiri -- string random 40 karakter, unik per
 * transaksi, cuma dikirim ke nomor WhatsApp yang terdaftar di aplikasi itu
 * (lihat generateInvoiceToken()), jadi praktis tidak bisa ditebak.
 */
class InvoiceController extends Controller
{
    public function show(string $token): View
    {
        // Cuma transaksi yang SUDAH "paid" yang boleh dibuka lewat sini --
        // invoice_token diisi bersamaan saat status jadi paid (lihat
        // sendInvoiceNotification()), jadi baris 'pending'/'failed'/'expired'
        // TIDAK PERNAH punya invoice_token untuk ditebak sama sekali, tapi
        // where('status', STATUS_PAID) tetap dipasang eksplisit di sini
        // sebagai lapisan jaga-jaga kedua.
        $payment = ApplicationPayment::with(['application.student', 'application.university', 'paymentGateway'])
            ->where('invoice_token', $token)
            ->where('status', ApplicationPayment::STATUS_PAID)
            ->firstOrFail();

        $purposeLabel = $payment->purpose === ApplicationPayment::PURPOSE_DEPARTURE_FEE
            ? 'Departure Fee'
            : 'Registration Fee';

        return view('invoice.show', [
            'payment' => $payment,
            'application' => $payment->application,
            'purposeLabel' => $purposeLabel,
        ]);
    }
}
