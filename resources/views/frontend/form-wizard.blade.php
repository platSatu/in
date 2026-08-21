<!--
    Perubahan dari versi sebelumnya:
    1. FormQuestion sekarang punya kolom `required` (boolean). Setiap .question-card
       dikasih data-required="1|0" dan tanda bintang merah di label kalau wajib.
    2. Tipe pertanyaan baru: textarea, date, dropdown (di luar text/number/single_choice/
       multiple_choice/major yang sudah ada).
    3. Validasi "required" untuk step pertanyaan sekarang dicek di JS sebelum boleh
       lanjut ke step review/submit — karena input single_choice & multiple_choice pakai
       radio/checkbox yang disembunyikan (d-none), jadi validasi HTML5 native `required`
       tidak bisa diandalkan untuk tipe itu.
    4. Step "Info" (Full Name, Email, WhatsApp phone number) sekarang divalidasi penuh di
       JS sebelum boleh next: nama cuma huruf/spasi/titik/apostrof/strip, email harus
       format standar, handphone harus angka saja 9-16 digit. Sebelumnya cuma dicek
       "tidak kosong".
    5. Background halaman pakai public/image/bg_quiz.png.
    6. === PAYMENT GATE === Kalau form ini requires_payment, ada step baru "Payment" di
       antara step Info dan step Pertanyaan. Step ini:
         - Memanggil POST /quiz/payment/init begitu step ditampilkan (nama/email/hp dari
           step Info dikirim ke server, transaksi dibuat ke gateway aktif).
         - Untuk Midtrans/iPaymu: user diarahkan ke halaman pembayaran hosted mereka.
         - Untuk Duitku: user pilih metode pembayaran dulu (VA/QRIS/dsb) baru diarahkan.
         - Setelah user kembali dari gateway (atau tanpa redirect sama sekali), wizard
           mem-poll GET /quiz/payment/{order_id}/status tiap beberapa detik. Step
           Pertanyaan (placement test) BARU bisa diakses begitu status = "paid".
         - Status "paid" itu sendiri HANYA diset oleh webhook resmi gateway di server
           (bukan oleh redirect browser), dan submit akhir (formWizardSubmit) juga
           memverifikasi ulang di server sebelum menyimpan jawaban. Jadi field
           `payment_order_id` di bawah ini murni bantu UI, bukan satu-satunya penjagaan.
    CATATAN: ini validasi FRONTEND. Validasi yang sama (name, email, handphone) juga
    sudah dipasang di server pada formWizardSubmit() (regex nama, email, digits_between:9,16).
-->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QUIZ | INASTUDY | CHINA EDUCATION CONSULTANT</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand: #e02424;
            --brand-dark: #8a0e0e;
            --brand-light: #fde3e3;
        }

        @php
            // Background & logo per-Form (opsional, nullable). Kalau form yang
            // sedang dibuka (selectedForm) punya background_image/logo sendiri
            // dan filenya benar ada di disk, dipakai; kalau tidak (termasuk saat
            // belum ada form yang dipilih -> halaman pilih form), fallback ke
            // default lama. Favicon (lihat <link rel="icon"> di atas) SENGAJA
            // tidak ikut berubah di sini, tetap Logo.png terus.
            $wizardBackground = (isset($selectedForm) && $selectedForm && $selectedForm->background_image && file_exists(public_path($selectedForm->background_image)))
                ? asset($selectedForm->background_image)
                : asset('image/bg_quiz.jpeg');
            $wizardLogo = (isset($selectedForm) && $selectedForm && $selectedForm->logo && file_exists(public_path($selectedForm->logo)))
                ? asset($selectedForm->logo)
                : asset('frontend/img/Logo.png');
        @endphp
        body {
            background-color: #f5f7fb;
            background-image: url('{{ $wizardBackground }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
            min-height: 100vh;
        }

        .brand-header {
            text-align: center;
            padding-top: 50px;
        }

        .brand-header img {
            height: 60px;
            margin-bottom: 10px;
        }

        .brand-header .brand-name {
            font-weight: 800;
            font-size: 15px;
            letter-spacing: .04em;
            color: var(--brand-dark);
            text-transform: uppercase;
        }

        .wizard-card {
            max-width: 620px;
            margin: auto;
            margin-top: 18px;
            margin-bottom: 60px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(20, 30, 60, .1);
            overflow: hidden;
            background: #fff;
        }

        .wizard-card .card-body {
            padding: 45px !important;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
            animation: fadeIn .35s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h3.form-title {
            font-weight: 800;
            color: #1d2333;
        }

        .progress {
            height: 8px;
            border-radius: 20px;
            background: #eef0f5;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--brand) 0%, var(--brand-dark) 100%);
            border-radius: 20px;
            transition: width .35s ease;
        }

        .step-indicator {
            font-size: 13.5px;
            color: #8a90a2;
            font-weight: 600;
            margin-bottom: 25px;
            letter-spacing: .02em;
        }

        .step-indicator strong {
            color: var(--brand);
        }

        .question-card {
            background: #f8f9fc;
            border: 1px solid #eef0f5;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
            transition: border-color .2s;
        }

        .question-card.has-error {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        /* Pertanyaan bercabang (conditional/nested questions): kartu anak diberi
           aksen garis kiri + latar putih supaya kelihatan "masuk" dari kartu
           induknya, selain indentasi margin-left yang dihitung per-depth lewat
           inline style di question-card.blade.php. Kartu anak ini di-render
           BENAR-BENAR NESTED tepat di bawah opsi pemicunya (lihat
           question-card.blade.php), jadi .nested-questions-group cuma pembungkus
           tipis untuk kasih jarak dari opsi di atasnya — bukan wrapper visual. */
        .question-card-nested {
            background: #fff;
            border-left: 3px solid var(--brand);
        }

        .nested-questions-group {
            margin-top: 12px;
            /* Jarak ke opsi/elemen berikutnya di bawahnya (mis. opsi "University
               Students" setelah cabang "School Name"/"Grade" milik "School
               Students") — tanpa ini, kartu cabang terakhir nempel langsung ke
               opsi berikutnya karena margin-bottom bawaannya di-nolkan (lihat
               aturan :last-child di bawah). */
            margin-bottom: 16px;
        }

        .nested-questions-group .question-card {
            margin-bottom: 12px;
        }

        .nested-questions-group .question-card:last-child {
            margin-bottom: 0;
        }

        /* Catatan sebelum test (pre_test_notice) — dibungkus jadi "card body"
           tersendiri supaya teks bebas yang diisi admin tidak tampil sebagai
           tumpukan teks polos begitu saja. Header judul ("Catatan Sebelum
           Placement Test") sudah dihapus sesuai permintaan, jadi box-nya
           sekarang cuma satu body saja — box-shadow tipis ditambahkan biar
           kelihatan sedikit 3D/terangkat dari background. */
        .notice-box {
            background: #f8f9fc;
            border: 1px solid #eef0f5;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(20, 20, 43, 0.07);
        }

        .notice-box-body {
            padding: 24px;
            font-size: 16.5px;
            font-weight: 700;
            line-height: 1.8;
            color: #333a4d;
        }

        .question-number {
            background: var(--brand);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
            font-size: 13.5px;
            flex-shrink: 0;
        }

        .question-card label.form-label,
        .question-card strong {
            font-weight: 600;
            color: #1d2333;
            font-size: 15px;
        }

        .required-mark {
            color: var(--brand);
            font-weight: 700;
            margin-left: 3px;
        }

        .optional-tag {
            font-size: 11px;
            font-weight: 600;
            color: #8a90a2;
            background: #eef0f5;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 .2rem rgba(224, 36, 36, .15);
        }

        .option-item {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Opsi berbentuk gambar (mis. soal Listening) — kartu gambar + radio/checkbox di bawahnya. */
        .option-item.option-item-image {
            flex-direction: column;
            align-items: stretch;
            height: 100%;
        }

        .option-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f1f2f6;
        }

        .option-item:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .option-item .option-check {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            border: 2px solid #c9cddb;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #fff;
            transition: all .2s;
        }

        .option-item.selected {
            border-color: var(--brand);
            background: var(--brand-light);
            font-weight: 600;
        }

        .option-item.selected .option-check {
            background: var(--brand);
            border-color: var(--brand);
        }

        .option-item.selected .option-check::after {
            content: "\2713";
        }

        .option-item.selected label {
            color: var(--brand-dark);
        }

        .form-option-checkbox {
            display: block;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }

        .form-option-checkbox:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .form-option-checkbox.selected {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .form-option-checkbox input:checked + .option-content {
            font-weight: 700;
            color: var(--brand-dark);
        }

        .form-text {
            font-size: 12.5px;
            color: #8a90a2;
        }

        .form-text i { color: var(--brand); }

        .error-message {
            color: var(--brand);
            font-size: 12px;
            margin-top: 8px;
            font-weight: 600;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .btn-brand {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 11px 26px;
            border-radius: 10px;
            transition: all .2s;
        }

        .btn-brand:hover {
            filter: brightness(1.08);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(224, 36, 36, .3);
        }

        .btn-brand:disabled {
            opacity: .5;
            transform: none;
            box-shadow: none;
        }

        .btn-outline-brand {
            border: 2px solid #e9ecef;
            color: #6b7186;
            font-weight: 700;
            padding: 10px 24px;
            border-radius: 10px;
            background: #fff;
            transition: all .2s;
        }

        .btn-outline-brand:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--brand-light);
        }

        .btn-submit {
            background: linear-gradient(135deg, #16a34a 0%, #0f7a37 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 11px 28px;
            border-radius: 10px;
            transition: all .2s;
        }

        .btn-submit:hover {
            filter: brightness(1.08);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(22, 163, 74, .3);
        }

        .alert-info-brand {
            background: var(--brand-light);
            border: 1px solid #f6c7c7;
            color: var(--brand-dark);
            border-radius: 12px;
        }

        .alert-success {
            border-radius: 12px;
        }

        #universityWrapper .badge {
            font-size: 13px;
            font-weight: 500;
            padding: 6px 10px;
            background: var(--brand-light) !important;
            color: var(--brand-dark) !important;
        }

        @media (max-width: 575px) {
            .wizard-card .card-body { padding: 28px !important; }
        }

        .site-footer {
            text-align: center;
            padding: 0 20px 30px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6b7186;
        }

        /* === PAYMENT STEP === */
        .payment-amount {
            font-size: 28px;
            font-weight: 800;
            color: var(--brand-dark);
        }

        .payment-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #eef0f5;
            border-top-color: var(--brand);
            border-radius: 50%;
            margin: 10px auto;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .payment-method-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            background: #fff;
            transition: all .2s;
        }

        .payment-method-item:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .payment-method-item img {
            height: 26px;
            max-width: 60px;
            object-fit: contain;
        }

        .payment-method-item .method-name {
            font-weight: 600;
            font-size: 14.5px;
            color: #1d2333;
        }

        .payment-status-box {
            text-align: center;
            padding: 10px 0;
        }

        /* === TIMER PLACEMENT TEST === */
        .quiz-timer-box {
            text-align: center;
            font-weight: 700;
            font-size: 15px;
            color: var(--brand-dark);
            background: var(--brand-light);
            border-radius: 10px;
            padding: 10px 14px;
        }

        .quiz-timer-box.quiz-timer-danger {
            background: var(--brand);
            color: #fff;
            animation: pulseTimer 1s infinite;
        }

        @keyframes pulseTimer {
            0%, 100% { opacity: 1; }
            50% { opacity: .55; }
        }

        /* === TIMER PEMBAYARAN === */
        .payment-countdown {
            text-align: center;
            font-size: 13.5px;
            font-weight: 600;
            color: #6b7186;
            margin-bottom: 10px;
        }

        .payment-countdown.payment-countdown-danger {
            color: var(--brand);
        }
    </style>
</head>

<body>

<div class="brand-header">
    <img src="{{ $wizardLogo }}" alt="InaStudy Logo">
    <div class="brand-name">InaStudy · China Education Consultant</div>
</div>

<div class="container">

    <div class="card wizard-card">

        <div class="card-body p-5">

            <h3 class="text-center form-title mb-2">
                {{ $selectedForm->name ?? 'Choose a Form' }}
            </h3>

            <p class="text-center text-muted mb-4">
                {{ $selectedForm->description ?? 'Please select the form you would like to fill out.' }}
            </p>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- === CALLBACK LINK ===
                 Hanya terisi kalau form yang baru disubmit diaktifkan sebagai "callback"
                 DAN (kalau requires_payment) pembayarannya sudah lolos verifikasi "paid"
                 di server (lihat FrontendController::formWizardSubmit). Murni tampilan
                 flash session sekali pakai, bukan sesuatu yang bisa diakses ulang lewat URL. --}}
            @if(session('callback_link'))
                <div class="alert alert-primary" style="border-left: 4px solid var(--brand);">
                    <i class="bi bi-link-45deg"></i>
                    <strong>Link Anda sudah siap:</strong><br>
                    <a href="{{ session('callback_link') }}" target="_blank" rel="noopener noreferrer" class="fw-bold">
                        {{ session('callback_link') }}
                    </a>
                </div>
            @endif

            @error('payment')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="progress mb-3">
                <div class="progress-bar" id="progressBar" style="width: 25%"></div>
            </div>

            <div class="step-indicator text-center">
               Step <strong id="currentStep">1</strong> of <strong id="totalSteps">3</strong>
            </div>

            <form action="{{ route('frontend.form.wizard.submit') }}" method="POST" id="wizardForm" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="form_id" value="{{ $selectedForm->id ?? '' }}">
                <input type="hidden" name="payment_order_id" id="paymentOrderIdInput" value="{{ request('order_id') }}">

                {{-- STEP: Select Form or User Info --}}
                <div class="step active" id="step-info">

                    @if(!$selectedForm)
                        {{-- Form Selection --}}
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-ui-checks-grid me-1"></i> Select a Form</label>
                            <select class="form-select" id="formSelect" onchange="selectForm()">
                                <option value="">-- Select a Form --</option>
                                @foreach($forms as $form)
                                    {{--
                                        data-wizard-url: URL cantik per-booth (/quiz/{slug}/{booth_slug}),
                                        dirender server-side lewat route() supaya JS tidak perlu tebak-tebak
                                        pola URL-nya sendiri. FormController::store()/update() SELALU
                                        mengisi slug & booth_slug untuk form yang dibuat/diupdate lewat form
                                        itu, TAPI form lama (dibuat sebelum kolom ini ada, mis. via seeder
                                        atau data lama) bisa saja masih null — route() akan melempar error
                                        "Missing required parameters" kalau salah satu kosong, jadi di sini
                                        di-generate cuma kalau dua-duanya benar terisi. Kalau kosong, atribut
                                        ini sengaja tidak dirender sama sekali; JS di selectForm() di bawah
                                        otomatis fallback ke URL lama (?form_id=...) untuk form ini.
                                    --}}
                                    <option value="{{ $form->id }}"
                                        @if($form->slug && $form->booth_slug)
                                            data-wizard-url="{{ route('frontend.form.wizard.slug', ['branchSlug' => $form->slug, 'boothSlug' => $form->booth_slug]) }}"
                                        @endif
                                    >
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-brand" onclick="nextStep()" id="btnSelectForm" disabled>
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    @else
                        {{-- User Info --}}
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-person me-1"></i> Full Name</label>
                            <input type="text" name="name" id="wizard-name" class="form-control" placeholder="Please enter your name" value="{{ old('name', $paymentPrefill->name ?? '') }}" required>
                            <div class="error-message" id="wizard-name-error">Nama hanya boleh huruf, spasi, titik, apostrof, dan strip.</div>
                            @error('name')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-envelope me-1"></i> Email</label>
                            <input type="email" name="email" id="wizard-email" class="form-control" placeholder="nama@email.com" value="{{ old('email', $paymentPrefill->email ?? '') }}" required>
                            <div class="error-message" id="wizard-email-error">Format email tidak valid.</div>
                            @error('email')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-whatsapp me-1"></i> WhatsApp phone number</label>
                            <input type="text" name="handphone" id="wizard-handphone" class="form-control" placeholder="62812xxxxxxxx" value="{{ old('handphone', $paymentPrefill->handphone ?? '') }}" required>
                            <div class="error-message" id="wizard-handphone-error">Nomor HP harus angka saja, minimal 9 dan maksimal 16 digit.</div>
                            @error('handphone')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($selectedForm->requires_payment)
                            <div class="form-text mb-3">
                                <i class="bi bi-info-circle"></i>
                                Form ini membutuhkan pembayaran sebesar
                                <strong>Rp {{ number_format((float) $selectedForm->payment_amount, 0, ',', '.') }}</strong>
                                sebelum bisa melanjutkan ke placement test.
                            </div>
                        @endif

                        <div class="text-end">
                            <button type="button" class="btn btn-brand" onclick="nextStep()">
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    @endif

                </div>

                {{-- STEP: Data Pribadi (hanya ada kalau form ini has_personal_data_stage) --}}
                @if($selectedForm && $selectedForm->has_personal_data_stage)
                    <div class="step" id="step-personal-data">

                        @if($personalDataQuestions->count() > 0)
                            @foreach($personalDataQuestions as $index => $question)
                                @include('frontend.partials.question-card', ['question' => $question, 'index' => $index])
                            @endforeach
                        @else
                            <div class="alert alert-warning">
                                This form does not have any personal data questions yet. Please contact the administrator.
                            </div>
                        @endif

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-brand" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-brand" onclick="nextStep()">
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                    </div>
                @endif

                {{-- STEP: Catatan (opsional per-Form, hanya ada kalau pre_test_notice diisi
                     admin) — selalu ditaruh setelah Data Pribadi/Info dan sebelum
                     Payment/Questions, lihat perhitungan stepOrder di bawah. --}}
                @if($selectedForm && filled($selectedForm->pre_test_notice))
                    <div class="step" id="step-notice">

                        @php
                            // Form lama nyimpen pre_test_notice sebagai plain text biasa,
                            // form baru (lewat toolbar Bold/Perbesar/Merah di form
                            // create/edit) nyimpennya sebagai HTML terbatas (span/strong/br,
                            // sudah disaring di server lewat FormController::
                            // sanitizeNoticeHtml() pas disimpan) — jadi aman ditampilkan
                            // apa adanya di sini. Yang plain text tetap di-escape + nl2br()
                            // seperti sebelumnya (nl2br() sudah menyisipkan <br> literal,
                            // jadi TIDAK pakai white-space:pre-line, nanti baris barunya dobel).
                            $noticeIsRichText = preg_match('/<(span|strong|b|br)\b/i', $selectedForm->pre_test_notice) === 1;
                            $noticeDisplayHtml = $noticeIsRichText
                                ? $selectedForm->pre_test_notice
                                : nl2br(e($selectedForm->pre_test_notice));
                        @endphp

                        <div class="notice-box">
                            <div class="notice-box-body">{!! $noticeDisplayHtml !!}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-brand" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-brand" onclick="nextStep()">
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                    </div>
                @endif

                {{-- STEP: Payment (hanya ada kalau form ini requires_payment) --}}
                @if($selectedForm && $selectedForm->requires_payment)
                    <div class="step" id="step-payment">

                        <div class="payment-countdown" id="paymentCountdownBox" style="display:none;">
                            <i class="bi bi-hourglass-split"></i> Selesaikan pembayaran dalam <span id="paymentCountdownDisplay">--:--</span>
                        </div>

                        <div class="payment-status-box" id="paymentStatusBox">
                            <div class="payment-spinner"></div>
                            <p class="mt-2 mb-0">Menyiapkan pembayaran...</p>
                        </div>

                        <div id="paymentContent" class="mt-3"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-brand" onclick="prevStep()">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-outline-brand" id="btnCheckPaymentStatus" onclick="checkPaymentStatusNow()" style="display:none;">
                                <i class="bi bi-arrow-clockwise"></i> Cek Status Pembayaran
                            </button>
                        </div>

                    </div>
                @endif

                {{-- STEP: Questions (placement test) --}}
                <div class="step" id="step-questions">

                    @if($selectedForm && $selectedForm->timer_enabled && $selectedForm->timer_duration_minutes)
                        <div class="quiz-timer-box mb-3" id="quizTimerBox">
                            <i class="bi bi-stopwatch"></i> Sisa Waktu Pengerjaan: <span id="quizTimerDisplay">--:--</span>
                        </div>
                    @endif

                    @if($selectedForm && $placementTestQuestions->count() > 0)
                        @foreach($placementTestQuestions as $index => $question)
                            @include('frontend.partials.question-card', ['question' => $question, 'index' => $index])
                        @endforeach
                    @elseif($selectedForm && $placementTestQuestions->count() == 0)
                        <div class="alert alert-warning">
                            This form does not have any questions yet. Please contact the administrator.
                        </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-brand" onclick="prevStep()">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-brand" onclick="nextStep()">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>

                </div>

                {{-- STEP: Review & Submit --}}
                <div class="step" id="step-review">

                    <div class="alert alert-info-brand">
                        <strong><i class="bi bi-info-circle me-1"></i> Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Please make sure all required questions (marked with *) have been answered.</li>
                            <li>Your answers will be saved in the system.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-brand" onclick="prevStep()">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-submit">
                            Submit <i class="bi bi-check-lg"></i>
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<footer class="site-footer">
    &copy; {{ date('Y') }} InaStudy. All rights reserved.
</footer>

<script>
    // Urutan step tergantung apakah form ini butuh pembayaran atau tidak, dan kalau
    // butuh, di posisi mana (diatur admin lewat "Posisi Pembayaran" saat create/edit form)
    // — dibaca sekali dari Blade, dipakai untuk semua navigasi step di bawah.
    const requiresPayment = {{ $selectedForm && $selectedForm->requires_payment ? 'true' : 'false' }};
    const paymentPosition = @json($selectedForm->payment_position ?? 'before_questions');
    const hasPersonalDataStage = {{ $selectedForm && $selectedForm->has_personal_data_stage ? 'true' : 'false' }};

    // True kalau form ini mengisi "Catatan Sebelum Test/Pembayaran" (opsional,
    // per-Form) — kalau true, 1 step tambahan ("step-notice") disisipkan ke
    // stepOrder di bawah. Kalau kosong, step ini otomatis di-skip sepenuhnya.
    const hasPreTestNotice = {{ $selectedForm && filled($selectedForm->pre_test_notice) ? 'true' : 'false' }};

    // True kalau redirect balik ke halaman ini itu HASIL dari submit akhir yang
    // gagal validasi server (misalnya name/email/handphone kosong/tidak valid).
    // Dulu tidak dicek sama sekali -> begitu ?order_id= masih ada di URL, JS di
    // bawah selalu lompat balik ke step Payment/Questions, jadi error yang
    // sebenarnya ada di step Info (dan pesan errornya) tidak pernah kelihatan
    // oleh user -> submit kelihatan "diem aja" padahal sebenarnya ditolak server.
    const hasValidationErrors = {{ $errors->any() ? 'true' : 'false' }};

    // Kalau form ini punya step "Data Pribadi", urutannya dikunci: info -> data
    // pribadi -> (pembayaran) -> placement test -> review. Setting "Posisi
    // Pembayaran" (before/after questions) sengaja DIABAIKAN di kasus ini —
    // pembayaran selalu ditaruh di antara data pribadi dan placement test,
    // sesuai alur yang sudah disepakati.
    const stepOrder = hasPersonalDataStage
        ? (requiresPayment
            ? ['step-info', 'step-personal-data', 'step-payment', 'step-questions', 'step-review']
            : ['step-info', 'step-personal-data', 'step-questions', 'step-review'])
        : (requiresPayment
            ? (paymentPosition === 'after_questions'
                ? ['step-info', 'step-questions', 'step-payment', 'step-review']
                : ['step-info', 'step-payment', 'step-questions', 'step-review'])
            : ['step-info', 'step-questions', 'step-review']);

    // Step "Catatan" (kalau diisi admin) selalu disisipkan tepat setelah Data
    // Pribadi (atau setelah Info kalau form tidak punya step Data Pribadi),
    // dan selalu sebelum Payment/Questions — apapun pengaturan
    // requires_payment/payment_position form ini (sesuai alur yang sudah
    // disepakati, lihat migration add_pre_test_notice_to_forms_table).
    if (hasPreTestNotice) {
        const insertAfter = hasPersonalDataStage ? 'step-personal-data' : 'step-info';
        stepOrder.splice(stepOrder.indexOf(insertAfter) + 1, 0, 'step-notice');
    }

    let currentStepIndex = 0;
    let paymentInitiated = false;
    let paymentPollTimer = null;
    let currentOrderId = document.getElementById('paymentOrderIdInput').value || null;

    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // === TIMER PLACEMENT TEST ===
    // "Aktifkan Timer" (timer_enabled) di form settings adalah gerbang utama —
    // kalau mati, timerEnabled di sini false dan seluruh logic timer di bawah
    // tidak pernah jalan, apapun isi timer_auto_save/timer_auto_restart di DB.
    const timerEnabled = {{ $selectedForm && $selectedForm->timer_enabled && $selectedForm->timer_duration_minutes ? 'true' : 'false' }};
    const timerDurationSeconds = {{ (int) (($selectedForm->timer_duration_minutes ?? 0) * 60) }};
    const timerAutoSave = {{ $selectedForm && $selectedForm->timer_auto_save ? 'true' : 'false' }};
    const timerAutoRestart = {{ $selectedForm && $selectedForm->timer_auto_restart ? 'true' : 'false' }};
    const timeoutSaveUrl = @json(route('frontend.form.wizard.timeout-save'));

    function updateProgress() {
        document.getElementById('totalSteps').textContent = stepOrder.length;
        const progress = ((currentStepIndex + 1) / stepOrder.length) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('currentStep').textContent = currentStepIndex + 1;
    }

    function showStep(index) {
        document.querySelectorAll('.step').forEach(function (el) {
            el.classList.remove('active');
        });

        const stepId = stepOrder[index];
        const stepEl = document.getElementById(stepId);
        if (stepEl) {
            stepEl.classList.add('active');
        }

        currentStepIndex = index;
        updateProgress();

        if (stepId === 'step-payment' && !paymentInitiated) {
            initPayment();
        }

        if (stepId === 'step-questions') {
            startQuizTimer();
        }
    }

    // Pertanyaan bercabang: kartu anak sekarang di-render BENAR-BENAR NESTED di
    // dalam markup opsi pemicunya (lihat frontend/partials/question-card.blade.php),
    // jadi sebuah .question-card bisa punya kartu anak sebagai DESCENDANT-nya
    // sendiri di DOM. Karena itu, semua pencarian input di sini WAJIB di-scope
    // lewat name="question_{questionId}" milik kartu ini sendiri (data-question-id)
    // — bukan sekadar "input pertama yang ketemu di dalam kartu" seperti sebelum
    // ada nesting, supaya tidak salah membaca input milik kartu ANAK sebagai
    // jawaban kartu INDUK (atau sebaliknya).
    function isQuestionAnswered(card) {
        const questionId = card.getAttribute('data-question-id');
        const baseName = 'question_' + questionId;

        const singleRadio = card.querySelector('input[type="radio"][name="' + baseName + '"]');
        if (singleRadio) {
            return !!card.querySelector('input[type="radio"][name="' + baseName + '"]:checked');
        }

        const checkboxes = card.querySelectorAll('input[type="checkbox"][name="' + baseName + '[]"]');
        if (checkboxes.length) {
            if (!Array.from(checkboxes).some(function (cb) { return cb.checked; })) {
                return false;
            }

            // Kalau opsi "Lainnya" yang dicentang, isian bebasnya juga wajib diisi —
            // jawaban "Lainnya" tanpa teks dianggap belum benar-benar terjawab.
            const checkedOther = Array.from(checkboxes).filter(function (cb) {
                return cb.checked && cb.classList.contains('option-other-checkbox');
            });
            for (let i = 0; i < checkedOther.length; i++) {
                const otherInput = card.querySelector(
                    '.other-text-input[data-for-option="' + checkedOther[i].value + '"]'
                );
                if (otherInput && otherInput.value.trim() === '') {
                    return false;
                }
            }

            return true;
        }

        const select = card.querySelector('select[name="' + baseName + '"]');
        if (select) {
            return select.value !== '';
        }

        const fileInput = card.querySelector('input[type="file"][name="' + baseName + '"]');
        if (fileInput) {
            return fileInput.files && fileInput.files.length > 0;
        }

        const textInput = card.querySelector(
            'input[type="text"][name="' + baseName + '"], ' +
            'input[type="number"][name="' + baseName + '"], ' +
            'input[type="date"][name="' + baseName + '"], ' +
            'textarea[name="' + baseName + '"]'
        );
        if (textInput) {
            return textInput.value.trim() !== '';
        }

        return true;
    }

    function validateQuestionsStep(containerId) {
        let isValid = true;
        let firstInvalidCard = null;

        document.querySelectorAll('#' + containerId + ' .question-card').forEach(function (card) {
            // Pertanyaan cabang yang sedang disembunyikan (opsi pemicunya belum/
            // tidak dipilih) tidak ikut divalidasi sama sekali — PENTING supaya
            // "required" di cabang yang sedang tidak relevan tidak memblokir submit.
            if (card.classList.contains('d-none')) {
                card.classList.remove('has-error');
                // :scope > .error-message: kartu anak sekarang nested di dalam kartu
                // ini sendiri (lihat isQuestionAnswered()), jadi querySelector biasa
                // bisa salah ambil punya kartu anak/cucu alih-alih punya kartu ini.
                const hiddenErrorEl = card.querySelector(':scope > .error-message');
                if (hiddenErrorEl) hiddenErrorEl.classList.remove('show');
                return;
            }

            const errorEl = card.querySelector(':scope > .error-message');
            const required = card.getAttribute('data-required') === '1';

            if (required && !isQuestionAnswered(card)) {
                isValid = false;
                card.classList.add('has-error');
                if (errorEl) errorEl.classList.add('show');
                if (!firstInvalidCard) firstInvalidCard = card;
            } else {
                card.classList.remove('has-error');
                if (errorEl) errorEl.classList.remove('show');
            }
        });

        if (firstInvalidCard) {
            firstInvalidCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    }

    // === PERTANYAAN BERCABANG (conditional/nested questions) ===================
    // Kosongkan semua input di dalam satu kartu pertanyaan, dipakai saat kartu itu
    // baru saja disembunyikan (opsi pemicunya di-uncheck/diganti) supaya jawaban
    // lama yang sudah tidak relevan tidak ikut nyangkut & tidak ikut tersubmit.
    function resetCardInputs(card) {
        card.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function (el) {
            el.checked = false;
        });
        card.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea').forEach(function (el) {
            el.value = '';
        });
        card.querySelectorAll('select').forEach(function (el) {
            el.value = '';
        });
        card.querySelectorAll('.option-item, .form-option-checkbox').forEach(function (el) {
            el.classList.remove('selected');
        });
        card.querySelectorAll('.other-text-input').forEach(function (el) {
            el.classList.add('d-none');
            el.value = '';
        });

        // Bersihkan status error di kartu ini SENDIRI, plus di semua kartu anak yang
        // ikut nested & ikut tersembunyi di dalamnya (cascading) — kartu anak sekarang
        // benar-benar nested secara fisik di DOM (lihat question-card.blade.php),
        // jadi ada kemungkinan beberapa lapis kartu anak/cucu sekaligus perlu direset.
        card.classList.remove('has-error');
        const ownErrorEl = card.querySelector(':scope > .error-message');
        if (ownErrorEl) ownErrorEl.classList.remove('show');

        card.querySelectorAll('.question-card').forEach(function (nested) {
            nested.classList.remove('has-error');
            const nestedErrorEl = nested.querySelector(':scope > .error-message');
            if (nestedErrorEl) nestedErrorEl.classList.remove('show');
        });
    }

    // Setiap kartu pertanyaan cabang (.question-card[data-parent-option-id]) di-render
    // BENAR-BENAR NESTED tepat di bawah opsi pemicunya di DOM (lihat
    // frontend/partials/question-card.blade.php), dengan atribut
    // data-parent-option-id menunjuk ke id radio/checkbox opsi pemicunya
    // (id="option_{id}"). Karena id opsi itu UUID yang unik secara GLOBAL (bukan
    // cuma unik per-pertanyaan), visibilitas tiap kartu bisa dihitung tanpa perlu
    // tahu struktur/kedalaman hierarkinya sama sekali — cukup diulang beberapa kali
    // sampai tidak ada perubahan lagi (fixed point), supaya cabang berlapis (anak
    // dari anak) ikut ke-resolve dengan benar dalam satu pemanggilan.
    function refreshNestedQuestionVisibility() {
        const nestedCards = document.querySelectorAll('.question-card[data-parent-option-id]');
        if (!nestedCards.length) return;

        let changed = true;
        let iterations = 0;

        while (changed && iterations < 20) {
            changed = false;
            iterations++;

            nestedCards.forEach(function (card) {
                const parentOptionId = card.getAttribute('data-parent-option-id');
                const trigger = document.getElementById('option_' + parentOptionId);
                const isTriggered = !!(trigger && trigger.checked);

                // Kartu ini baru benar-benar dianggap "aktif" kalau opsi pemicunya
                // sendiri berada di kartu yang sedang tampil (bukan tertutup oleh
                // leluhurnya sendiri) — supaya cabang di dalam cabang yang sedang
                // tersembunyi tidak ikut muncul duluan.
                const triggerCard = trigger ? trigger.closest('.question-card') : null;
                const parentVisible = !triggerCard || !triggerCard.classList.contains('d-none');

                const shouldShow = isTriggered && parentVisible;
                const isHidden = card.classList.contains('d-none');

                if (shouldShow && isHidden) {
                    card.classList.remove('d-none');
                    changed = true;
                } else if (!shouldShow && !isHidden) {
                    card.classList.add('d-none');
                    resetCardInputs(card);
                    changed = true;
                }
            });
        }
    }

    // Pola yang sama dengan validasi server (formWizardSubmit):
    // nama huruf/spasi/titik/apostrof/strip, email format standar, handphone 9-16 digit angka.
    const NAME_PATTERN = /^[\p{L}\s.'-]+$/u;
    const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const PHONE_PATTERN = /^[0-9]{9,16}$/;

    function toggleFieldError(input, errorEl, isValid) {
        if (!input) return true;

        if (!isValid) {
            if (errorEl) errorEl.classList.add('show');
            input.classList.add('is-invalid');
            return false;
        }

        if (errorEl) errorEl.classList.remove('show');
        input.classList.remove('is-invalid');
        return true;
    }

    function validateStep1() {
        const nameInput = document.getElementById('wizard-name');
        const emailInput = document.getElementById('wizard-email');
        const phoneInput = document.getElementById('wizard-handphone');

        // Kalau elemen-elemen ini tidak ada (mis. masih di layar pilih form), anggap valid.
        if (!nameInput || !emailInput || !phoneInput) {
            return true;
        }

        const nameError = document.getElementById('wizard-name-error');
        const emailError = document.getElementById('wizard-email-error');
        const phoneError = document.getElementById('wizard-handphone-error');

        const nameOk = toggleFieldError(nameInput, nameError, NAME_PATTERN.test(nameInput.value.trim()));
        const emailOk = toggleFieldError(emailInput, emailError, EMAIL_PATTERN.test(emailInput.value.trim()));
        const phoneOk = toggleFieldError(phoneInput, phoneError, PHONE_PATTERN.test(phoneInput.value.trim()));

        if (!nameOk) {
            nameInput.focus();
        } else if (!emailOk) {
            emailInput.focus();
        } else if (!phoneOk) {
            phoneInput.focus();
        }

        return nameOk && emailOk && phoneOk;
    }

    function isPaymentConfirmed() {
        return !requiresPayment || document.getElementById('paymentOrderIdInput').value !== '';
    }

    function nextStep() {
        const currentStepId = stepOrder[currentStepIndex];

        if (currentStepId === 'step-info' && !validateStep1()) {
            return;
        }

        if (currentStepId === 'step-payment' && !isPaymentConfirmed()) {
            // Belum dikonfirmasi paid, jangan biarkan lompat ke pertanyaan.
            return;
        }

        if (currentStepId === 'step-personal-data' && !validateQuestionsStep('step-personal-data')) {
            return;
        }

        if (currentStepId === 'step-questions' && !validateQuestionsStep('step-questions')) {
            return;
        }

        if (currentStepIndex < stepOrder.length - 1) {
            showStep(currentStepIndex + 1);
        }
    }

    function prevStep() {
        if (currentStepIndex > 0) {
            const leavingPayment = stepOrder[currentStepIndex] === 'step-payment';

            showStep(currentStepIndex - 1);

            if (leavingPayment) {
                // User balik dari step payment ke step sebelumnya: reset supaya transaksi
                // baru dibuat lagi kalau dia lanjut ke payment sekali lagi.
                stopPaymentPolling();
                paymentInitiated = false;
                currentOrderId = null;
                document.getElementById('paymentOrderIdInput').value = '';
            }
        }
    }

    function selectForm() {
        const formSelect = document.getElementById('formSelect');
        const btnSelectForm = document.getElementById('btnSelectForm');

        if (formSelect.value) {
            btnSelectForm.disabled = false;
            btnSelectForm.onclick = function() {
                // Redirect ke URL cantik per-booth (/quiz/{slug}/{booth_slug}), bukan
                // lagi ke ?form_id=... — supaya begitu dipilih dari daftar quiz, hasil
                // klik-nya konsisten sama dengan URL yang dibagikan (mis. WA/QR code
                // yang mengarah ke inagroup.asia/quiz/pluit/boothviii26).
                const selectedOption = formSelect.options[formSelect.selectedIndex];
                const wizardUrl = selectedOption ? selectedOption.getAttribute('data-wizard-url') : null;
                window.location.href = wizardUrl || ('{{ route("frontend.form.wizard") }}?form_id=' + formSelect.value);
            };
        } else {
            btnSelectForm.disabled = true;
        }
    }

    // Single choice toggle
    function toggleSingleOption(element, questionId) {
        // Remove selected class from all options in this group. Di-scope supaya
        // CUMA opsi milik kartu `parent` sendiri yang ke-uncheck — kartu anak
        // (pertanyaan bercabang) sekarang nested fisik di dalam salah satu opsi di
        // sini, jadi .option-item miliknya sendiri (kalau tipe anaknya juga single/
        // multiple choice) TIDAK boleh ikut kena reset "selected" dari toggle induk.
        const parent = element.closest('.question-card');
        parent.querySelectorAll('.option-item').forEach(function(opt) {
            if (opt.closest('.question-card') !== parent) return;
            opt.classList.remove('selected');
        });
        // Add selected class to clicked option
        element.classList.add('selected');
        // Check the radio
        const radio = element.querySelector('input[type="radio"]');
        radio.checked = true;

        parent.classList.remove('has-error');
        const errorEl = parent.querySelector(':scope > .error-message');
        if (errorEl) errorEl.classList.remove('show');

        // Pertanyaan bercabang: opsi ini bisa jadi pemicu pertanyaan anak.
        refreshNestedQuestionVisibility();
    }

    // Multiple choice toggle
    function toggleMultipleOption(element) {
        element.classList.toggle('selected');
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = element.classList.contains('selected');

        // Opsi "Lainnya" (is_other, lihat frontend/partials/question-card.blade.php):
        // kolom isian bebasnya ada sebagai sibling di luar `element`, ditampilkan
        // hanya selama checkbox-nya dicentang. Dikosongkan lagi begitu dicentang-off
        // supaya tidak ada teks lama yang nyangkut ikut terkirim.
        if (checkbox.classList.contains('option-other-checkbox')) {
            const otherInput = element.parentElement.querySelector(
                '.other-text-input[data-for-option="' + checkbox.value + '"]'
            );
            if (otherInput) {
                otherInput.classList.toggle('d-none', !checkbox.checked);
                if (checkbox.checked) {
                    otherInput.focus();
                } else {
                    otherInput.value = '';
                }
            }
        }

        const parent = element.closest('.question-card');
        parent.classList.remove('has-error');
        const errorEl = parent.querySelector(':scope > .error-message');
        if (errorEl) errorEl.classList.remove('show');

        // Pertanyaan bercabang: opsi ini bisa jadi pemicu pertanyaan anak.
        refreshNestedQuestionVisibility();
    }

    // Clear error state as soon as user starts answering text/number/date/select/dropdown
    // Selector di-scope ke class .question-card saja (bukan diprefix per-step lagi)
    // supaya listener ini otomatis mencakup step-questions MAUPUN step-personal-data,
    // karena keduanya sama-sama merender partial question-card yang sama.
    document.querySelectorAll('.question-card select, .question-card input[type="text"], .question-card input[type="number"], .question-card input[type="date"], .question-card input[type="file"], .question-card textarea').forEach(function (el) {
        el.addEventListener('input', function () {
            const card = el.closest('.question-card');
            card.classList.remove('has-error');
            const errorEl = card.querySelector(':scope > .error-message');
            if (errorEl) errorEl.classList.remove('show');
        });
        el.addEventListener('change', function () {
            const card = el.closest('.question-card');
            card.classList.remove('has-error');
            const errorEl = card.querySelector(':scope > .error-message');
            if (errorEl) errorEl.classList.remove('show');
        });
    });

    // Clear error state on step 1 fields as soon as user edits them
    ['wizard-name', 'wizard-email', 'wizard-handphone'].forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            el.classList.remove('is-invalid');
            const errorEl = document.getElementById(id + '-error');
            if (errorEl) errorEl.classList.remove('show');
        });
    });

    // === TIMER PLACEMENT TEST ===================================================
    // Satu hitungan mundur untuk SELURUH step Placement Test (bukan per soal),
    // dimulai sekali begitu step-questions pertama kali ditampilkan (lihat
    // showStep()). Tidak ikut di-reset kalau user pindah ke step-review lalu balik
    // lagi ke step-questions — timer terus jalan sampai submit berhasil atau habis.

    let quizTimerInterval = null;
    let quizTimerDeadline = null;
    let quizTimerStarted = false;

    function formatMMSS(totalSeconds) {
        const s = Math.max(0, Math.floor(totalSeconds));
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }

    function startQuizTimer() {
        if (!timerEnabled || quizTimerStarted) return;

        quizTimerStarted = true;
        quizTimerDeadline = Date.now() + (timerDurationSeconds * 1000);
        tickQuizTimer();
        quizTimerInterval = window.setInterval(tickQuizTimer, 1000);
    }

    function tickQuizTimer() {
        const display = document.getElementById('quizTimerDisplay');
        if (!display) return;

        const remainingSeconds = Math.ceil((quizTimerDeadline - Date.now()) / 1000);
        const box = document.getElementById('quizTimerBox');

        if (remainingSeconds <= 0) {
            display.textContent = '00:00';
            window.clearInterval(quizTimerInterval);
            quizTimerInterval = null;
            handleQuizTimeout();
            return;
        }

        display.textContent = formatMMSS(remainingSeconds);
        if (box) box.classList.toggle('quiz-timer-danger', remainingSeconds <= 60);
    }

    // Kosongkan semua input di dalam step-questions (radio/checkbox/text/select),
    // dipakai timer_auto_restart supaya soal benar-benar "mulai dari nol" lagi
    // TANPA reload halaman penuh — reload penuh akan memicu ulang alur pembayaran/
    // data pribadi dan menambah view_count form (lihat FrontendController::
    // buildFormWizardView()), yang keduanya tidak diinginkan di sini.
    function resetQuestionsStepUI() {
        const container = document.getElementById('step-questions');
        if (!container) return;

        container.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function (el) {
            el.checked = false;
        });
        container.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea').forEach(function (el) {
            el.value = '';
        });
        container.querySelectorAll('select').forEach(function (el) {
            el.value = '';
        });
        container.querySelectorAll('.option-item, .form-option-checkbox').forEach(function (el) {
            el.classList.remove('selected');
        });
        container.querySelectorAll('.question-card').forEach(function (el) {
            el.classList.remove('has-error');
            const errorEl = el.querySelector(':scope > .error-message');
            if (errorEl) errorEl.classList.remove('show');
        });

        // Semua radio/checkbox baru saja di-uncheck di atas, jadi seluruh kartu
        // pertanyaan cabang (kalau ada) harus ikut kembali tersembunyi.
        refreshNestedQuestionVisibility();
    }

    async function postFormDataJson(url, formData) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: formData,
        });

        const data = await response.json().catch(function () { return {}; });

        if (!response.ok) {
            throw new Error(data.message || 'Gagal menyimpan jawaban.');
        }

        return data;
    }

    // Urutan begitu waktu habis: simpan dulu (kalau timer_auto_save aktif). Kalau
    // hasil simpan itu "final" (timer_auto_restart MATI, ini percobaan terakhir),
    // server sudah memperlakukannya identik dengan submit manual — langsung navigasi
    // ke redirect_url supaya peserta melihat layar "Thank you" yang sama, BUKAN
    // berhenti macet di layar "Waktu habis!". Kalau bukan final (auto-restart AKTIF),
    // baru reset ke soal pertama dan mulai timer baru — sesuai yang disepakati:
    // jawaban yang sempat terisi tidak boleh hilang percuma sebelum soal direset.
    async function handleQuizTimeout() {
        const box = document.getElementById('quizTimerBox');
        if (box) {
            box.classList.add('quiz-timer-danger');
            box.innerHTML = '<i class="bi bi-stopwatch"></i> Waktu habis!';
        }

        if (timerAutoSave) {
            try {
                const result = await postFormDataJson(timeoutSaveUrl, new FormData(document.getElementById('wizardForm')));

                if (result && result.final && result.redirect_url) {
                    window.location.href = result.redirect_url;
                    return;
                }
            } catch (err) {
                // Diamkan: kalau auto-save gagal (mis. jaringan), tetap lanjut ke
                // auto-restart di bawah supaya peserta tidak macet di layar "waktu
                // habis" — konsekuensinya jawaban yang sempat terisi tidak
                // tersimpan untuk percobaan yang baru saja habis waktunya itu saja.
            }
        }

        if (timerAutoRestart) {
            resetQuestionsStepUI();

            const questionsIndex = stepOrder.indexOf('step-questions');
            if (questionsIndex !== -1 && currentStepIndex !== questionsIndex) {
                showStep(questionsIndex);
            }

            quizTimerDeadline = Date.now() + (timerDurationSeconds * 1000);
            if (box) box.classList.remove('quiz-timer-danger');
            tickQuizTimer();
            quizTimerInterval = window.setInterval(tickQuizTimer, 1000);
        }
    }

    // === TIMER PEMBAYARAN =======================================================
    // expires_at didapat dari response init()/status() (lihat FormPaymentController),
    // server_time dipakai untuk hitung offset supaya countdown tetap akurat walau
    // jam device peserta meleset dari jam server.

    let paymentCountdownInterval = null;
    let paymentCountdownDeadline = null;
    let paymentServerTimeOffsetMs = 0;

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
            // Server yang jadi sumber kebenaran status "expired" (self-heal di
            // FormPaymentController::status()) — di sini cukup paksa 1x pengecekan
            // status supaya UI expired langsung muncul, tidak nunggu interval
            // polling 4 detik berikutnya.
            checkPaymentStatus();
            return;
        }

        display.textContent = formatMMSS(remainingSeconds);
        if (box) box.classList.toggle('payment-countdown-danger', remainingSeconds <= 60);
    }

    // === PAYMENT ===============================================================

    function paymentStatusBox(html) {
        document.getElementById('paymentStatusBox').innerHTML = html;
    }

    function paymentContent(html) {
        document.getElementById('paymentContent').innerHTML = html;
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

    function initPayment() {
        paymentInitiated = true;
        paymentStatusBox(
            '<div class="payment-spinner"></div><p class="mt-2 mb-0">Menyiapkan pembayaran...</p>'
        );
        paymentContent('');
        document.getElementById('btnCheckPaymentStatus').style.display = 'none';

        const payload = {
            form_id: document.querySelector('input[name="form_id"]').value,
            name: document.getElementById('wizard-name').value.trim(),
            email: document.getElementById('wizard-email').value.trim(),
            handphone: document.getElementById('wizard-handphone').value.trim(),
        };

        postJson('{{ route('frontend.payment.init') }}', payload)
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
                paymentInitiated = false;
                paymentStatusBox('<p class="text-danger mb-0">' + err.message + '</p>');
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

    function selectDuitkuMethod(methodCode, el) {
        document.querySelectorAll('.payment-method-item').forEach(function (item) {
            item.style.pointerEvents = 'none';
            item.style.opacity = '.6';
        });

        paymentStatusBox('<div class="payment-spinner"></div><p class="mt-2 mb-0">Menyiapkan pembayaran...</p>');

        postJson('{{ route('frontend.payment.duitku.select-method') }}', {
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
        paymentStatusBox(
            '<p class="mb-2"><i class="bi bi-shield-check"></i> Anda akan diarahkan ke halaman pembayaran aman...</p>'
        );
        paymentContent(
            '<div class="text-center">' +
            '<a href="' + redirectUrl + '" class="btn btn-brand">Lanjut ke Pembayaran <i class="bi bi-arrow-right"></i></a>' +
            '<p class="form-text mt-3">Setelah selesai membayar, halaman ini akan otomatis lanjut ke placement test.</p>' +
            '</div>'
        );

        // Mulai polling status dari sekarang juga, supaya kalau user sudah bayar di
        // tab lain / browser yang sama tanpa perlu balik ke halaman ini secara manual.
        startPaymentPolling();

        window.setTimeout(function () {
            window.location.href = redirectUrl;
        }, 1500);
    }

    function startPaymentPolling() {
        stopPaymentPolling();
        document.getElementById('btnCheckPaymentStatus').style.display = '';
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

        fetch('{{ url('/quiz/payment') }}/' + currentOrderId + '/status', {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'paid') {
                    stopPaymentPolling();
                    stopPaymentCountdown();
                    document.getElementById('paymentOrderIdInput').value = currentOrderId;
                    paymentStatusBox('<p class="text-success mb-0"><i class="bi bi-check-circle-fill"></i> Pembayaran berhasil dikonfirmasi!</p>');
                    paymentContent('');
                    window.setTimeout(function () { nextStep(); }, 800);
                } else if (data.status === 'failed' || data.status === 'expired') {
                    stopPaymentPolling();
                    stopPaymentCountdown();
                    paymentStatusBox('<p class="text-danger mb-0"><i class="bi bi-x-circle-fill"></i> Pembayaran gagal/kedaluwarsa.</p>');
                    paymentContent(
                        '<div class="text-center"><button type="button" class="btn btn-outline-brand" onclick="paymentInitiated=false; initPayment();">Coba Lagi</button></div>'
                    );
                } else {
                    // status "pending" -> diam saja, terus polling. expires_at bisa saja
                    // berubah/terisi belakangan (mis. dari null saat awal init non-Duitku
                    // yang gagal, lalu retry) — resync di sini juga, bukan cuma saat init().
                    setPaymentExpiry(data.expires_at, data.server_time);
                }
            })
            .catch(function () {
                // Diamkan error jaringan sesaat, coba lagi di interval berikutnya.
            });
    }

    // Kalau halaman ini dibuka lagi dengan ?order_id=... (user baru kembali dari
    // gateway pembayaran), langsung lompat ke step Payment dan lanjutkan polling
    // tanpa membuat transaksi baru.
    document.addEventListener('DOMContentLoaded', function () {
        updateProgress();

        // Pertanyaan bercabang: hitung status tampil/sembunyi awal begitu halaman
        // dimuat (jaga-jaga kalau suatu saat ada opsi yang sudah ter-checked lewat
        // server, mis. prefill) — aman dipanggil walau belum ada yang tercentang.
        refreshNestedQuestionVisibility();

        // PENTING: kalau redirect ini sebenarnya hasil submit akhir yang gagal
        // validasi (name/email/handphone kosong/tidak valid, dsb), JANGAN lompat
        // ke step lain walau order_id masih nempel di URL — biarkan tetap di step
        // Info (step aktif default dari server, lihat class="step active" di
        // #step-info) supaya pesan error yang sudah dirender di step itu benar-benar
        // kelihatan oleh user, bukan ketutup step Payment/Questions.
        if (hasValidationErrors) {
            return;
        }

        if (requiresPayment && currentOrderId) {
            paymentInitiated = true;
            const paymentIndex = stepOrder.indexOf('step-payment');
            showStep(paymentIndex);
            paymentStatusBox('<div class="payment-spinner"></div><p class="mt-2 mb-0">Mengecek status pembayaran...</p>');
            startPaymentPolling();
            checkPaymentStatus();
        }
    });
</script>

</body>

</html>
