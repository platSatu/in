<!--
    FASE 6 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- halaman
    invoice publik, dibuka dari link yang dikirim via WhatsApp setelah
    Registration Fee/Departure Fee lunas (lihat
    Payment\FormPaymentController::sendInvoiceNotification() &
    InvoiceController).

    "Download PDF" di sini SENGAJA lewat window.print() (dialog print
    browser, yang di hampir semua browser modern punya opsi "Save as PDF")
    -- BUKAN PDF yang di-generate di server (butuh library baru semacam
    dompdf yang belum terpasang di project ini). CSS @media print di bawah
    menyembunyikan tombol & elemen non-esensial supaya hasil print/PDF-nya
    rapi, cuma menyisakan invoice-nya sendiri.
-->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $application->application_no }} | INASTUDY</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #C8102E; --brand-dark: #a30d25; }
        * { box-sizing: border-box; }
        body {
            background: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
        }
        .page-wrap { max-width: 720px; margin: 0 auto; padding: 32px 16px 60px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 16px; }
        .btn-brand {
            background: var(--brand); color: #fff; border: none;
            padding: 10px 18px; border-radius: 10px; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-outline-brand {
            border: 2px solid #e9ecef; color: #6b7186; font-weight: 700; padding: 9px 16px;
            border-radius: 10px; background: #fff; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .invoice-box {
            background: #fff;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
        }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .invoice-header img { height: 42px; margin-bottom: 8px; }
        .invoice-header .brand-name { font-weight: 800; font-size: 14px; letter-spacing: .03em; color: var(--brand-dark); }
        .invoice-title { text-align: right; }
        .invoice-title h4 { font-weight: 800; margin-bottom: 2px; }
        .invoice-title .paid-badge {
            display: inline-block; background: #e8f8ee; color: #1a9c53;
            font-weight: 700; font-size: 12.5px; padding: 4px 12px; border-radius: 20px;
        }
        hr { border-top: 1px solid #eef1f8; margin: 20px 0; }
        .detail-row {
            display: flex; justify-content: space-between; gap: 12px;
            padding: 8px 0; font-size: 14px;
        }
        .detail-row .label { color: #8a90a2; }
        .detail-row .value { font-weight: 600; text-align: right; }
        .amount-box {
            background: #f8f9fc; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0;
        }
        .amount-box .amount-label { font-size: 13px; color: #8a90a2; font-weight: 600; margin-bottom: 4px; }
        .amount-box .amount-value { font-size: 28px; font-weight: 800; color: var(--brand-dark); }
        .footer-note { text-align: center; color: #8a90a2; font-size: 12.5px; margin-top: 24px; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; max-width: 100%; }
            .invoice-box { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>

<body>

    <div class="page-wrap">

        <div class="toolbar">
            <button type="button" class="btn-outline-brand" onclick="window.print()">
                <i class="bi bi-printer"></i> Print / Download PDF
            </button>
        </div>

        <div class="invoice-box">
            <div class="invoice-header">
                <div>
                    <img src="{{ asset('frontend/img/Logo.png') }}" alt="InaStudy">
                    <div class="brand-name">InaStudy · China Education Consultant</div>
                </div>
                <div class="invoice-title">
                    <h4>INVOICE</h4>
                    <span class="paid-badge"><i class="bi bi-check-circle-fill"></i> PAID</span>
                </div>
            </div>

            <hr>

            <div class="detail-row">
                <span class="label">Application No.</span>
                <span class="value">{{ $application->application_no }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Student</span>
                <span class="value">{{ trim(($application->student->first_name ?? '') . ' ' . ($application->student->last_name ?? '')) ?: '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">University</span>
                <span class="value">{{ $application->university->name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Description</span>
                <span class="value">{{ $purposeLabel }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Payment Method</span>
                <span class="value text-uppercase">{{ $payment->gateway ?? '-' }}{{ $payment->payment_method ? ' · ' . $payment->payment_method : '' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Order ID</span>
                <span class="value">{{ $payment->order_id }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Paid At</span>
                <span class="value">{{ optional($payment->paid_at)->format('d M Y, H:i') }}</span>
            </div>

            <div class="amount-box">
                <div class="amount-label">TOTAL PAID</div>
                <div class="amount-value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
            </div>

            <p class="footer-note">
                This is an official payment receipt from InaStudy. Please keep this invoice for your records.
            </p>
        </div>

    </div>

</body>

</html>
