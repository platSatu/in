<?php

namespace App\Http\Controllers;

use App\Models\ApplicationPayment;
use App\Models\FormPayment;
use App\Models\FormQuestionOption;
use Illuminate\View\View;

/**
 * FASE 6 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- halaman
 * invoice PUBLIK (TANPA login) dari link yang dikirim setelah pembayaran lunas
 * (Payment\FormPaymentController::sendInvoiceNotification()):
 *  - ApplicationPayment (Registration Fee / Departure Fee), via WhatsApp;
 *  - FormPayment form berjenis Ina Visa (10 November 2026), via WhatsApp & email.
 *
 * SENGAJA tanpa middleware 'auth': dibuka langsung dari chat/email, sering dari
 * device lain. Keamanannya dari 'invoice_token' -- string random 40 karakter, unik
 * per transaksi, hanya diisi saat lunas & hanya dikirim ke pembayarnya.
 */
class InvoiceController extends Controller
{
    public function show(string $token): View
    {
        // where status paid tetap dipasang sebagai lapisan jaga-jaga kedua (token hanya diisi saat lunas).
        $payment = ApplicationPayment::with(['application.student', 'application.university'])
            ->where('invoice_token', $token)->where('status', ApplicationPayment::STATUS_PAID)->first()
            ?? FormPayment::with('form:id,name')->where('invoice_token', $token)->where('status', 'paid')->firstOrFail();

        if ($payment instanceof ApplicationPayment) {
            $application = $payment->application;
            $number = $application->application_no;
            $rows = [
                'Application No.' => $application->application_no,
                'Student' => trim(($application->student->first_name ?? '') . ' ' . ($application->student->last_name ?? '')) ?: '-',
                'University' => $application->university->name ?? '-',
                'Description' => $payment->purpose === ApplicationPayment::PURPOSE_DEPARTURE_FEE ? 'Departure Fee' : 'Registration Fee',
            ];
        } else {
            $number = $payment->order_id;
            $rows = [
                'Name' => $payment->name ?: '-',
                'Email' => $payment->email ?: '-',
                'Description' => $payment->form->name ?? 'Ina Visa',
                // Paket yang dibayar (nama pilihan diambil dari database, bukan dari isian peserta).
                'Package' => FormQuestionOption::whereIn('id', $payment->package_option_ids ?? [])->pluck('option_text')->implode(', ') ?: '-',
            ];
        }

        return view('invoice.show', [
            'payment' => $payment,
            'number' => $number,
            'rows' => $rows + [
                'Payment Method' => strtoupper(($payment->gateway ?? '-') . ($payment->payment_method ? ' · ' . $payment->payment_method : '')),
                'Order ID' => $payment->order_id,
                'Paid At' => optional($payment->paid_at)->format('d M Y, H:i'),
            ],
        ]);
    }
}
