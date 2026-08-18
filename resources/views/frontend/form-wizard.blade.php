<!--
    Perubahan dari versi sebelumnya:
    1. FormQuestion sekarang punya kolom `required` (boolean). Setiap .question-card
       dikasih data-required="1|0" dan tanda bintang merah di label kalau wajib.
    2. Tipe pertanyaan baru: textarea, date, dropdown (di luar text/number/single_choice/
       multiple_choice/major yang sudah ada).
    3. Validasi "required" untuk step 2 (pertanyaan) sekarang dicek di JS sebelum boleh
       lanjut ke step 3 / submit — karena input single_choice & multiple_choice pakai
       radio/checkbox yang disembunyikan (d-none), jadi validasi HTML5 native `required`
       tidak bisa diandalkan untuk tipe itu.
    4. Step 1 (Full Name, Email, WhatsApp phone number) sekarang divalidasi penuh di JS
       sebelum boleh next: nama cuma huruf/spasi/titik/apostrof/strip, email harus format
       standar, handphone harus angka saja 9-16 digit. Sebelumnya cuma dicek "tidak kosong".
    5. Background halaman pakai public/image/bg_quiz.png.
    CATATAN: ini validasi FRONTEND. Validasi yang sama (name, email, handphone) juga
    sudah dipasang di server pada formWizardSubmit() (regex nama, email, digits_between:9,16).
-->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        body {
            background-color: #f5f7fb;
            background-image: url('{{ asset('image/bg_quiz.jpeg') }}');
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
    </style>
</head>

<body>

<div class="brand-header">
    <img src="{{ asset('frontend/img/Logo.png') }}" alt="InaStudy Logo">
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

            <div class="progress mb-3">
                <div class="progress-bar" id="progressBar" style="width: 25%"></div>
            </div>

            <div class="step-indicator text-center">
               Step <strong id="currentStep">1</strong> of <strong id="totalSteps">3</strong>
            </div>

            <form action="{{ route('frontend.form.wizard.submit') }}" method="POST" id="wizardForm">
                @csrf

                <input type="hidden" name="form_id" value="{{ $selectedForm->id ?? '' }}">

                {{-- STEP 1: Select Form or User Info --}}
                <div class="step active" id="step1">

                    @if(!$selectedForm)
                        {{-- Form Selection --}}
                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-ui-checks-grid me-1"></i> Select a Form</label>
                            <select class="form-select" id="formSelect" onchange="selectForm()">
                                <option value="">-- Select a Form --</option>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}">{{ $form->name }}</option>
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
                            <input type="text" name="name" id="wizard-name" class="form-control" placeholder="Please enter your name" required>
                            <div class="error-message" id="wizard-name-error">Nama hanya boleh huruf, spasi, titik, apostrof, dan strip.</div>
                            @error('name')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-envelope me-1"></i> Email</label>
                            <input type="email" name="email" id="wizard-email" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" required>
                            <div class="error-message" id="wizard-email-error">Format email tidak valid.</div>
                            @error('email')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-whatsapp me-1"></i> WhatsApp phone number</label>
                            <input type="text" name="handphone" id="wizard-handphone" class="form-control" placeholder="62812xxxxxxxx" required>
                            <div class="error-message" id="wizard-handphone-error">Nomor HP harus angka saja, minimal 9 dan maksimal 16 digit.</div>
                            @error('handphone')
                                <div class="error-message show">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-brand" onclick="nextStep()">
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    @endif

                </div>

                {{-- STEP 2: Questions --}}
                <div class="step" id="step2">

                    @if($selectedForm && $questions->count() > 0)
                        @foreach($questions as $index => $question)
                            <div class="question-card" data-required="{{ $question->required ? '1' : '0' }}" data-question-id="{{ $question->id }}">
                                <div class="mb-3 d-flex align-items-center">
                                    <span class="question-number">{{ $index + 1 }}</span>
                                    <strong>
                                        {{ $question->question_text }}
                                        @if($question->required)
                                            <span class="required-mark">*</span>
                                        @else
                                            <span class="optional-tag">Opsional</span>
                                        @endif
                                    </strong>
                                </div>

                                @if($question->type === 'text')
                                    <input type="text"
                                        name="question_{{ $question->id }}"
                                        class="form-control"
                                        placeholder="Your Answer">
                                @elseif($question->type === 'textarea')
                                    <textarea
                                        name="question_{{ $question->id }}"
                                        class="form-control"
                                        rows="4"
                                        placeholder="Your Answer"></textarea>
                                @elseif($question->type === 'number')
                                    <input type="number"
                                        name="question_{{ $question->id }}"
                                        class="form-control"
                                        placeholder="Enter a number">
                                @elseif($question->type === 'date')
                                    <input type="date"
                                        name="question_{{ $question->id }}"
                                        class="form-control">
                                @elseif($question->type === 'single_choice')
                                    @foreach($question->options as $option)
                                        <div class="form-check option-item" onclick="toggleSingleOption(this, '{{ $question->id }}')">
                                            <span class="option-check"></span>
                                            <input type="radio"
                                                name="question_{{ $question->id }}"
                                                value="{{ $option->id }}"
                                                class="form-check-input d-none"
                                                id="option_{{ $option->id }}">
                                            <label class="form-check-label" for="option_{{ $option->id }}">
                                                {{ $option->option_text }}
                                            </label>
                                        </div>
                                    @endforeach
                                @elseif($question->type === 'multiple_choice')
                                    @foreach($question->options as $option)
                                        <div class="form-option-checkbox" onclick="toggleMultipleOption(this)">
                                            <input type="checkbox"
                                                name="question_{{ $question->id }}[]"
                                                value="{{ $option->id }}"
                                                class="form-check-input"
                                                id="option_{{ $option->id }}">
                                            <label class="form-check-label option-content" for="option_{{ $option->id }}">
                                                {{ $option->option_text }}
                                            </label>
                                        </div>
                                    @endforeach
                                @elseif($question->type === 'dropdown')
                                    <select class="form-select" name="question_{{ $question->id }}">
                                        <option value="">-- Select an option --</option>
                                        @foreach($question->options as $option)
                                            <option value="{{ $option->id }}">{{ $option->option_text }}</option>
                                        @endforeach
                                    </select>
                                @elseif($question->type === 'major')
                                    {{-- Optionnya bukan dari form_question_options, tapi dari tabel majors --}}
                                    <select class="form-select" name="question_{{ $question->id }}">
                                        <option value="">-- Select a Major --</option>
                                        @foreach($majors as $major)
                                            <option value="{{ $major->id }}">{{ $major->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text mt-2">
                                        <i class="bi bi-whatsapp"></i>
                                        The list of universities for this major will be sent to your WhatsApp after you submit the form.
                                    </div>
                                @endif

                                <div class="error-message">This question is required, please fill in your answer.</div>
                            </div>
                        @endforeach
                    @elseif($selectedForm && $questions->count() == 0)
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

                {{-- STEP 3: Review & Submit --}}
                <div class="step" id="step3">

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
    let currentStep = 1;
    const totalSteps = 3;

    function updateProgress() {
        const progress = (currentStep / totalSteps) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('currentStep').textContent = currentStep;
    }

    function showStep(step) {
        document.querySelectorAll('.step').forEach(function(el) {
            el.classList.remove('active');
        });
        document.getElementById('step' + step).classList.add('active');
        currentStep = step;
        updateProgress();
    }

    function isQuestionAnswered(card) {
        const singleRadio = card.querySelector('input[type="radio"]');
        if (singleRadio) {
            return !!card.querySelector('input[type="radio"]:checked');
        }

        const checkboxes = card.querySelectorAll('input[type="checkbox"]');
        if (checkboxes.length) {
            return Array.from(checkboxes).some(function (cb) { return cb.checked; });
        }

        const select = card.querySelector('select');
        if (select) {
            return select.value !== '';
        }

        const textInput = card.querySelector('input[type="text"], input[type="number"], input[type="date"], textarea');
        if (textInput) {
            return textInput.value.trim() !== '';
        }

        return true;
    }

    function validateStep2() {
        let isValid = true;
        let firstInvalidCard = null;

        document.querySelectorAll('#step2 .question-card').forEach(function (card) {
            const errorEl = card.querySelector('.error-message');
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

    function nextStep() {
        // Validate step 1 before proceeding
        if (currentStep === 1 && !validateStep1()) {
            return;
        }

        // Validate step 2 (required questions) before proceeding to review/submit
        if (currentStep === 2 && !validateStep2()) {
            return;
        }

        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    }

    function selectForm() {
        const formSelect = document.getElementById('formSelect');
        const btnSelectForm = document.getElementById('btnSelectForm');

        if (formSelect.value) {
            btnSelectForm.disabled = false;
            btnSelectForm.onclick = function() {
                // Redirect to form wizard with selected form
                window.location.href = '{{ route("frontend.form.wizard") }}?form_id=' + formSelect.value;
            };
        } else {
            btnSelectForm.disabled = true;
        }
    }

    // Single choice toggle
    function toggleSingleOption(element, questionId) {
        // Remove selected class from all options in this group
        const parent = element.closest('.question-card');
        parent.querySelectorAll('.option-item').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        // Add selected class to clicked option
        element.classList.add('selected');
        // Check the radio
        const radio = element.querySelector('input[type="radio"]');
        radio.checked = true;

        parent.classList.remove('has-error');
        const errorEl = parent.querySelector('.error-message');
        if (errorEl) errorEl.classList.remove('show');
    }

    // Multiple choice toggle
    function toggleMultipleOption(element) {
        element.classList.toggle('selected');
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = element.classList.contains('selected');

        const parent = element.closest('.question-card');
        parent.classList.remove('has-error');
        const errorEl = parent.querySelector('.error-message');
        if (errorEl) errorEl.classList.remove('show');
    }

    // Clear error state as soon as user starts answering text/number/date/select/dropdown
    document.querySelectorAll('#step2 .question-card select, #step2 .question-card input[type="text"], #step2 .question-card input[type="number"], #step2 .question-card input[type="date"], #step2 .question-card textarea').forEach(function (el) {
        el.addEventListener('input', function () {
            const card = el.closest('.question-card');
            card.classList.remove('has-error');
            const errorEl = card.querySelector('.error-message');
            if (errorEl) errorEl.classList.remove('show');
        });
        el.addEventListener('change', function () {
            const card = el.closest('.question-card');
            card.classList.remove('has-error');
            const errorEl = card.querySelector('.error-message');
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

    // Initialize
    updateProgress();
</script>

</body>

</html>
