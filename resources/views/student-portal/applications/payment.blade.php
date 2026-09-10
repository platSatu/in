<!--
    Fase 2 -- "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).

    Halaman pembayaran Registration Fee (purpose=registration_fee, Step 3
    sebelum Study Plan & Upload Documents terbuka) DAN Departure Fee
    (purpose=departure_fee, Step 4 setelah dokumen di-approve admin -- lihat
    Fase 5). Satu file dipakai untuk 2 purpose ini, dibedakan lewat variabel
    $purpose dari StudentPortal\ApplicationPaymentController::show().

    Alur JS di bawah ini SENGAJA meniru pola step "Payment" di
    frontend/form-wizard.blade.php (init -> select-method (Duitku) / redirect
    (Midtrans & iPaymu) -> polling status -> redirect setelah "paid") supaya
    perilakunya konsisten dengan fitur pembayaran Quiz yang sudah berjalan --
    TAPI endpoint yang dipanggil beda (student-portal.applications.payment.*,
    lihat StudentPortal\ApplicationPaymentController), dan tidak ada step
    lain di sekitarnya -- halaman ini berdiri sendiri, bukan bagian dari
    wizard multi-step.
-->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $purposeLabel }} - {{ $application->application_no }} | INASTUDY</title>
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
        .topbar { background: #fff; border-bottom: 1px solid #eef1f8; padding: 16px 0; }
        .topbar a { color: #6b7186; text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { color: var(--brand); }
        .page-wrap { max-width: 620px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .card-box h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .card-box .subtitle { color: #6b7186; font-size: 14.5px; margin-bottom: 0; }
        .payment-amount { font-size: 30px; font-weight: 800; color: var(--brand-dark); text-align: center; margin: 6px 0 18px; }
        .payment-purpose-tag {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6b7186;
            background: #f1f2f6;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .payment-spinner {
            width: 40px; height: 40px;
            border: 4px solid #eef0f5;
            border-top-color: var(--brand);
            border-radius: 50%;
            margin: 10px auto;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .payment-status-box { text-align: center; padding: 10px 0; }
        .payment-method-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 10px;
            margin-bottom: 10px; cursor: pointer; background: #fff; transition: all .2s;
        }
        .payment-method-item:hover { border-color: var(--brand); background: #fbe6ea; }
        .payment-method-item img { height: 26px; max-width: 60px; object-fit: contain; }
        .payment-method-item .method-name { font-weight: 600; font-size: 14.5px; color: #1d2333; }
        .payment-countdown { text-align: center; font-size: 13.5px; font-weight: 600; color: #6b7186; margin-bottom: 10px; }
        .payment-countdown.payment-countdown-danger { color: var(--brand); }
        .btn-brand {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff; border: none; font-weight: 700; padding: 12px 26px;
            border-radius: 10px; transition: all .2s; width: 100%;
        }
        .btn-brand:hover { filter: brightness(1.08); color: #fff; }
        .btn-brand:disabled { opacity: .5; }
        .btn-outline-brand {
            border: 2px solid #e9ecef; color: #6b7186; font-weight: 700; padding: 10px 24px;
            border-radius: 10px; background: #fff; transition: all .2s;
        }
        .btn-outline-brand:hover { border-color: var(--brand); color: var(--brand); background: #fbe6ea; }
        .btn-link-secondary { color: #6b7186; font-weight: 600; font-size: 13.5px; text-decoration: none; }
        .btn-link-secondary:hover { color: var(--brand); }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container">
            <a href="{{ route('student-portal.applications.show', $application->id) }}">
                <i class="bi bi-arrow-left"></i> Back to Application Summary
            </a>
        </div>
    </div>

    <div class="page-wrap">

        @if(session('status'))
            <div class="alert alert-info" style="border-radius:12px;">{{ session('status') }}</div>
        @endif

        <div class="card-box">
            <div class="payment-purpose-tag">{{ $purposeLabel }}</div>
            <h1>{{ $purposeLabel }}</h1>
            <p class="subtitle">
                {{ $application->university->name ?? '-' }} &middot; {{ $application->application_no }}
            </p>
        </div>

        <div class="card-box">

            @if(empty($amount) || $amount <= 0)
                {{--
                    Nominal KEDUA purpose ini (Registration Fee & Departure Fee)
                    diisi manual oleh admin per-aplikasi -- lihat catatan di
                    ApplicationPaymentController::init(). Kalau belum diisi,
                    tidak ada tombol bayar sama sekali (mencegah transaksi
                    Rp 0 ke gateway), cuma pesan jelas + tombol refresh.
                --}}
                <div class="alert alert-warning" style="border-radius:12px;">
                    <i class="bi bi-info-circle"></i>
                    Nominal {{ $purposeLabel }} belum diatur oleh admin. Silakan hubungi admin kami, atau coba
                    muat ulang halaman ini beberapa saat lagi.
                </div>
                <button type="button" class="btn btn-outline-brand w-100" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Muat Ulang
                </button>
            @else
                <div class="payment-amount">Rp {{ number_format((float) $amount, 0, ',', '.') }}</div>

                <div class="payment-countdown" id="paymentCountdownBox" style="display:none;">
                    <i class="bi bi-hourglass-split"></i> Selesaikan pembayaran dalam <span id="paymentCountdownDisplay">--:--</span>
                </div>

                <div class="payment-status-box" id="paymentStatusBox">
                    <p class="mb-0" style="color:#6b7186;font-size:14px;">
                        Klik tombol di bawah untuk melanjutkan ke pembayaran.
                    </p>
                </div>

                <div id="paymentContent" class="mt-3">
                    <button type="button" class="btn btn-brand" id="btnStartPayment" onclick="startPayment()">
                        <i class="bi bi-credit-card"></i> Bayar Sekarang
                    </button>
                </div>

                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-brand" id="btnCheckPaymentStatus" onclick="checkPaymentStatusNow()" style="display:none;">
                        <i class="bi bi-arrow-clockwise"></i> Cek Status Pembayaran
                    </button>
                </div>
            @endif

        </div>

        <div class="text-center">
            <a href="{{ route('student-portal.applications.show', $application->id) }}" class="btn-link-secondary">
                View Application Summary
            </a>
        </div>

    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const APPLICATION_ID = @json($application->id);
        const PAYMENT_PURPOSE = @json($purpose);

        // Ke mana user diarahkan begitu status "paid" terkonfirmasi -- sama
        // persis logika redirect $alreadyPaid di
        // ApplicationPaymentController::show(), supaya konsisten kalau
        // halaman ini dibuka ulang setelah lunas.
        const NEXT_URL_AFTER_PAID = @json(
            $purpose === \App\Models\ApplicationPayment::PURPOSE_REGISTRATION_FEE
                ? route('student-portal.applications.documents.edit', $application->id)
                : route('student-portal.applications.show', $application->id)
        );

        // Kalau baru saja kembali dari halaman gateway (lihat
        // ApplicationPaymentController::return(), yang flash 'resume_order_id'),
        // ATAU ada transaksi "pending" yang belum kedaluwarsa dari percobaan
        // sebelumnya -- langsung lanjutkan polling status TANPA membuat
        // transaksi baru, supaya klik "Bayar Sekarang" berulang kali tidak
        // menumpuk baris application_payments yang tidak perlu.
        let currentOrderId = @json(session('resume_order_id'));
        @if($pendingPayment)
            if (!currentOrderId) {
                currentOrderId = @json($pendingPayment->order_id);
            }
        @endif

        let paymentPollTimer = null;
        let paymentCountdownInterval = null;
        let paymentCountdownDeadline = null;
        let paymentServerTimeOffsetMs = 0;

        function formatMMSS(totalSeconds) {
            const m = Math.floor(totalSeconds / 60);
            const s = totalSeconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        // Guard null di semua helper ini: kalau nominal belum diatur admin,
        // blok #paymentStatusBox/#paymentContent/#btnCheckPaymentStatus
        // sengaja TIDAK dirender sama sekali (lihat kondisi "amount kosong"
        // di bagian Blade di atas), jadi elemen-elemen ini bisa saja tidak
        // ada di DOM.
        function paymentStatusBox(html) {
            const el = document.getElementById('paymentStatusBox');
            if (el) el.innerHTML = html;
        }

        function paymentContent(html) {
            const el = document.getElementById('paymentContent');
            if (el) el.innerHTML = html;
        }

        async function postJson(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify(body),
            });

            const data = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan, silakan coba lagi.');
            }

            return data;
        }

        function setPaymentExpiry(expiresAtIso, serverTimeIso) {
            if (!expiresAtIso) {
                stopPaymentCountdown();
                return;
            }

            if (serverTimeIso) {
                paymentServerTimeOffsetMs = new Date(serverTimeIso).getTime() - Date.now();
            }

            paymentCountdownDeadline = new Date(expiresAtIso).getTime();
            startPaymentCountdown();
        }

        function startPaymentCountdown() {
            stopPaymentCountdown();
            const box = document.getElementById('paymentCountdownBox');
            if (box) box.style.display = '';
            tickPaymentCountdown();
            paymentCountdownInterval = window.setInterval(tickPaymentCountdown, 1000);
        }

        function stopPaymentCountdown() {
            if (paymentCountdownInterval) {
                window.clearInterval(paymentCountdownInterval);
                paymentCountdownInterval = null;
            }
            const box = document.getElementById('paymentCountdownBox');
            if (box) box.style.display = 'none';
        }

        function tickPaymentCountdown() {
            const display = document.getElementById('paymentCountdownDisplay');
            if (!display || !paymentCountdownDeadline) return;

            const nowAdjusted = Date.now() + paymentServerTimeOffsetMs;
            const remainingSeconds = Math.ceil((paymentCountdownDeadline - nowAdjusted) / 1000);
            const box = document.getElementById('paymentCountdownBox');

            if (remainingSeconds <= 0) {
                display.textContent = '00:00';
                stopPaymentCountdown();
                checkPaymentStatus();
                return;
            }

            display.textContent = formatMMSS(remainingSeconds);
            if (box) box.classList.toggle('payment-countdown-danger', remainingSeconds <= 60);
        }

        function startPayment() {
            document.getElementById('btnStartPayment') && (document.getElementById('btnStartPayment').disabled = true);
            paymentStatusBox('<div class="payment-spinner"></div><p class="mt-2 mb-0">Menyiapkan pembayaran...</p>');
            paymentContent('');

            postJson(@json(route('student-portal.applications.payment.init')), {
                application_id: APPLICATION_ID,
                purpose: PAYMENT_PURPOSE,
            })
                .then(function (data) {
                    currentOrderId = data.order_id;
                    setPaymentExpiry(data.expires_at, data.server_time);

                    if (data.mode === 'select-method') {
                        renderDuitkuMethods(data.methods || []);
                        return;
                    }

                    if (data.mode === 'redirect' && data.redirect_url) {
                        startRedirectCountdown(data.redirect_url);
                        return;
                    }

                    paymentStatusBox('<p class="text-danger mb-0">Gagal menyiapkan pembayaran. Silakan coba lagi.</p>');
                })
                .catch(function (err) {
                    paymentStatusBox('<p class="text-danger mb-0">' + err.message + '</p>');
                    paymentContent('<button type="button" class="btn btn-brand" onclick="startPayment()"><i class="bi bi-arrow-clockwise"></i> Coba Lagi</button>');
                });
        }

        function renderDuitkuMethods(methods) {
            paymentStatusBox('<p class="mb-0"><i class="bi bi-credit-card"></i> Silakan pilih metode pembayaran:</p>');

            if (!methods.length) {
                paymentContent('<p class="text-danger">Tidak ada metode pembayaran yang tersedia saat ini.</p>');
                return;
            }

            let html = '<div class="mt-3">';
            methods.forEach(function (method) {
                html += '<div class="payment-method-item" onclick="selectDuitkuMethod(\'' + method.code + '\', this)">';
                if (method.image) {
                    html += '<img src="' + method.image + '" alt="">';
                }
                html += '<span class="method-name">' + method.name + '</span>';
                html += '</div>';
            });
            html += '</div>';

            paymentContent(html);
        }

        function selectDuitkuMethod(methodCode) {
            document.querySelectorAll('.payment-method-item').forEach(function (item) {
                item.style.pointerEvents = 'none';
                item.style.opacity = '.6';
            });

            paymentStatusBox('<div class="payment-spinner"></div><p class="mt-2 mb-0">Menyiapkan pembayaran...</p>');

            postJson(@json(route('student-portal.applications.payment.duitku.select-method')), {
                order_id: currentOrderId,
                payment_method: methodCode,
            })
                .then(function (data) {
                    if (data.redirect_url) {
                        startRedirectCountdown(data.redirect_url);
                    } else {
                        paymentStatusBox('<p class="text-danger mb-0">Gagal menyiapkan pembayaran. Silakan coba lagi.</p>');
                    }
                })
                .catch(function (err) {
                    paymentStatusBox('<p class="text-danger mb-0">' + err.message + '</p>');
                });
        }

        function startRedirectCountdown(redirectUrl) {
            paymentStatusBox('<p class="mb-2"><i class="bi bi-shield-check"></i> Anda akan diarahkan ke halaman pembayaran aman...</p>');
            paymentContent(
                '<div class="text-center">' +
                '<a href="' + redirectUrl + '" class="btn btn-brand">Lanjut ke Pembayaran <i class="bi bi-arrow-right"></i></a>' +
                '<p class="text-muted mt-3" style="font-size:12.5px;">Setelah selesai membayar, halaman ini akan otomatis lanjut.</p>' +
                '</div>'
            );

            startPaymentPolling();

            window.setTimeout(function () {
                window.location.href = redirectUrl;
            }, 1500);
        }

        function startPaymentPolling() {
            stopPaymentPolling();
            const checkBtn = document.getElementById('btnCheckPaymentStatus');
            if (checkBtn) checkBtn.style.display = '';
            paymentPollTimer = window.setInterval(checkPaymentStatus, 4000);
        }

        function stopPaymentPolling() {
            if (paymentPollTimer) {
                window.clearInterval(paymentPollTimer);
                paymentPollTimer = null;
            }
        }

        function checkPaymentStatusNow() {
            checkPaymentStatus();
        }

        function checkPaymentStatus() {
            if (!currentOrderId) return;

            fetch(@json(url('/applications/payment')) + '/' + currentOrderId + '/status', {
                headers: { 'Accept': 'application/json' },
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.status === 'paid') {
                        stopPaymentPolling();
                        stopPaymentCountdown();
                        paymentStatusBox('<p class="text-success mb-0"><i class="bi bi-check-circle-fill"></i> Pembayaran berhasil dikonfirmasi!</p>');
                        paymentContent('');
                        window.setTimeout(function () { window.location.href = NEXT_URL_AFTER_PAID; }, 800);
                    } else if (data.status === 'failed' || data.status === 'expired') {
                        stopPaymentPolling();
                        stopPaymentCountdown();
                        paymentStatusBox('<p class="text-danger mb-0"><i class="bi bi-x-circle-fill"></i> Pembayaran gagal/kedaluwarsa.</p>');
                        paymentContent('<button type="button" class="btn btn-brand" onclick="startPayment()"><i class="bi bi-arrow-clockwise"></i> Coba Lagi</button>');
                        const checkBtn = document.getElementById('btnCheckPaymentStatus');
                        if (checkBtn) checkBtn.style.display = 'none';
                    } else {
                        setPaymentExpiry(data.expires_at, data.server_time);
                    }
                })
                .catch(function () {
                    // Diamkan error jaringan sesaat, coba lagi di interval berikutnya.
                });
        }

        // Kalau ada order_id untuk dilanjutkan (baru kembali dari gateway,
        // atau ada transaksi pending sebelumnya) -- langsung mulai polling,
        // tanpa menunggu klik "Bayar Sekarang".
        document.addEventListener('DOMContentLoaded', function () {
            if (currentOrderId) {
                const startBtn = document.getElementById('btnStartPayment');
                if (startBtn) {
                    paymentContent('');
                }
                paymentStatusBox('<div class="payment-spinner"></div><p class="mt-2 mb-0">Memeriksa status pembayaran...</p>');
                startPaymentPolling();
                checkPaymentStatus();
            }
        });
    </script>

</body>

</html>
