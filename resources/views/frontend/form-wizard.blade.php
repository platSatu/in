<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Wizard - InaStudy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .wizard-card {
            max-width: 600px;
            margin: auto;
            margin-top: 70px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .progress {
            height: 8px;
        }

        .step-indicator {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .step-indicator strong {
            color: #0d6efd;
        }

        .question-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .question-number {
            background: #0d6efd;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .option-item {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-item:hover {
            border-color: #0d6efd;
            background: #f0f7ff;
        }

        .option-item.selected {
            border-color: #0d6efd;
            background: #e7f1ff;
        }

        .form-option-checkbox {
            display: block;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-option-checkbox:hover {
            border-color: #0d6efd;
            background: #f0f7ff;
        }

        .form-option-checkbox input:checked + .option-content {
            font-weight: 600;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card wizard-card">

        <div class="card-body p-5">

            <h3 class="text-center mb-2">
                {{ $selectedForm->name ?? 'Pilih Form' }}
            </h3>

            <p class="text-center text-muted mb-4">
                {{ $selectedForm->description ?? 'Silakan pilih formulir yang ingin您 isi.' }}
            </p>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="progress mb-4">
                <div class="progress-bar" id="progressBar" style="width: 25%"></div>
            </div>

            <div class="step-indicator text-center">
                Langkah <strong id="currentStep">1</strong> dari <strong id="totalSteps">3</strong>
            </div>

            <form action="{{ route('frontend.form.wizard.submit') }}" method="POST" id="wizardForm">
                @csrf

                <input type="hidden" name="form_id" value="{{ $selectedForm->id ?? '' }}">

                {{-- STEP 1: Select Form or User Info --}}
                <div class="step active" id="step1">

                    @if(!$selectedForm)
                        {{-- Form Selection --}}
                        <div class="mb-4">
                            <label class="form-label">Pilih Formulir</label>
                            <select class="form-select" id="formSelect" onchange="selectForm()">
                                <option value="">-- Pilih Formulir --</option>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}">{{ $form->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-primary" onclick="nextStep()" id="btnSelectForm" disabled>
                                Lanjut →
                            </button>
                        </div>
                    @else
                        {{-- User Info --}}
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama lengkap" required>
                            @error('name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" name="handphone" class="form-control" placeholder="62812xxxxxxxx" required>
                            @error('handphone')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Lanjut →
                            </button>
                        </div>
                    @endif

                </div>

                {{-- STEP 2: Questions --}}
                <div class="step" id="step2">

                    @if($selectedForm && $questions->count() > 0)
                        @foreach($questions as $index => $question)
                            <div class="question-card">
                                <div class="mb-3">
                                    <span class="question-number">{{ $index + 1 }}</span>
                                    <strong>{{ $question->question_text }}</strong>
                                </div>

                                @if($question->type === 'text')
                                    <input type="text" 
                                        name="question_{{ $question->id }}" 
                                        class="form-control"
                                        placeholder="Jawaban Anda">
                                @elseif($question->type === 'number')
                                    <input type="number" 
                                        name="question_{{ $question->id }}" 
                                        class="form-control"
                                        placeholder="Masukkan angka">
                                @elseif($question->type === 'single_choice')
                                    @foreach($question->options as $option)
                                        <div class="form-check option-item" onclick="toggleSingleOption(this, '{{ $question->id }}')">
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
                                @endif
                            </div>
                        @endforeach
                    @elseif($selectedForm && $questions->count() == 0)
                        <div class="alert alert-warning">
                            Form ini belum memiliki pertanyaan. Silakan hubungi administrator.
                        </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">
                            ← Kembali
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">
                            Lanjut →
                        </button>
                    </div>

                </div>

                {{-- STEP 3: Review & Submit --}}
                <div class="step" id="step3">

                    <div class="alert alert-info">
                        <strong>Petunjuk:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Pastikan semua pertanyaan sudah dijawab.</li>
                            <li>Jawaban Anda akan disimpan dalam sistem.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">
                            ← Kembali
                        </button>
                        <button type="submit" class="btn btn-success">
                            Submit ✓
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

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

    function nextStep() {
        // Validate step 1 before proceeding
        if (currentStep === 1) {
            const nameInput = document.querySelector('input[name="name"]');
            const phoneInput = document.querySelector('input[name="handphone"]');
            
            if (nameInput && !nameInput.value) {
                nameInput.focus();
                return;
            }
            if (phoneInput && !phoneInput.value) {
                phoneInput.focus();
                return;
            }
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
    }

    // Multiple choice toggle
    function toggleMultipleOption(element) {
        element.classList.toggle('selected');
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = element.classList.contains('selected');
    }

    // Initialize
    updateProgress();
</script>

</body>

</html>
