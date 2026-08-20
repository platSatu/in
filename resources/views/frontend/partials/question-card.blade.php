{{--
    Partial kartu pertanyaan, dipakai bareng oleh step "Data Pribadi" dan step
    "Placement Test" di form-wizard.blade.php (sebelumnya block ini cuma ada
    sekali, inline, di dalam #step-questions). Variabel yang dibutuhkan:
    - $question : App\Models\FormQuestion (with('options'))
    - $index    : int, index dalam loop (dipakai untuk nomor urut & huruf opsi)
    - $majors   : Illuminate\Support\Collection, hanya dipakai untuk type 'major'

    Variabel opsional (pertanyaan bercabang / conditional-nested questions):
    - $depth          : int, seberapa dalam nesting-nya (0 = pertanyaan utama)
    - $parentOptionId : string|null, id opsi (form_question_options.id) yang
                        jadi pemicu kartu ini. Kartu dengan $depth > 0 dirender
                        SEBAGAI SIBLING (bukan di-nest secara fisik di dalam
                        div opsi pemicunya) supaya JS visibilitas (lihat
                        refreshNestedQuestionVisibility() di form-wizard.blade.php)
                        bisa menghitung status tampil/sembunyi tiap kartu tanpa
                        perlu tahu struktur DOM-nya — cukup lewat atribut
                        data-parent-option-id yang menunjuk ke id radio/checkbox
                        opsi pemicunya. Disembunyikan (class d-none) secara
                        default, baru ditampilkan begitu opsi pemicunya dipilih.
--}}
@php
    $depth = $depth ?? 0;
    $parentOptionId = $parentOptionId ?? null;
    $nestedIndent = min($depth, 6) * 28;
@endphp

@if ($depth <= 12)
<div class="question-card {{ $depth > 0 ? 'question-card-nested d-none' : '' }}"
    data-required="{{ $question->required ? '1' : '0' }}"
    data-question-id="{{ $question->id }}"
    data-depth="{{ $depth }}"
    @if($parentOptionId) data-parent-option-id="{{ $parentOptionId }}" @endif
    @if($depth > 0) style="margin-left: {{ $nestedIndent }}px;" @endif
>
    <div class="mb-3 d-flex align-items-start">
        <span class="question-number">{{ $index + 1 }}</span>
        <div class="flex-grow-1">
            <strong>
                {{ $question->question_text }}
                @if($question->required)
                    <span class="required-mark">*</span>
                @else
                    <span class="optional-tag">Opsional</span>
                @endif
            </strong>

            @if($question->description)
                <div class="text-muted mt-1" style="font-size: 14px;">{{ $question->description }}</div>
            @endif

            @if($question->audio)
                <div class="mt-2">
                    <audio controls preload="none" src="{{ asset($question->audio) }}" style="width: 100%; max-width: 320px;"></audio>
                </div>
            @endif

            @if($question->image)
                <div class="mt-3">
                    <img src="{{ asset($question->image) }}" alt=""
                        style="max-width: 100%; max-height: 280px; border-radius: 12px;">
                </div>
            @endif
        </div>
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
        @php $hasImageOptions = $question->options->contains(fn ($o) => !empty($o->image)); @endphp
        <div class="{{ $hasImageOptions ? 'row g-3' : '' }}">
            @foreach($question->options as $option)
                <div class="{{ $hasImageOptions ? 'col-6 col-md-4' : '' }}">
                    <div class="form-check option-item {{ $hasImageOptions ? 'option-item-image' : '' }}"
                        onclick="toggleSingleOption(this, '{{ $question->id }}')">
                        @if($option->image)
                            <img src="{{ asset($option->image) }}" alt="" class="option-image">
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            <span class="option-check"></span>
                            <input type="radio"
                                name="question_{{ $question->id }}"
                                value="{{ $option->id }}"
                                class="form-check-input d-none"
                                id="option_{{ $option->id }}">
                            <label class="form-check-label mb-0" for="option_{{ $option->id }}">
                                {{ $option->option_text ?: chr(65 + $loop->index) }}
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($question->type === 'multiple_choice')
        @php $hasImageOptions = $question->options->contains(fn ($o) => !empty($o->image)); @endphp
        <div class="{{ $hasImageOptions ? 'row g-3' : '' }}">
            @foreach($question->options as $option)
                <div class="{{ $hasImageOptions ? 'col-6 col-md-4' : '' }}">
                    <div class="{{ $hasImageOptions ? 'option-item option-item-image' : 'form-option-checkbox' }}"
                        onclick="toggleMultipleOption(this)">
                        @if($option->image)
                            <img src="{{ asset($option->image) }}" alt="" class="option-image">
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox"
                                name="question_{{ $question->id }}[]"
                                value="{{ $option->id }}"
                                class="form-check-input {{ $option->is_other ? 'option-other-checkbox' : '' }}"
                                id="option_{{ $option->id }}">
                            <label class="form-check-label option-content mb-0" for="option_{{ $option->id }}">
                                {{ $option->option_text ?: chr(65 + $loop->index) }}
                            </label>
                        </div>
                    </div>

                    @if($option->is_other)
                        {{--
                            Kolom isian bebas untuk opsi "Lainnya". Ditaruh DI LUAR div
                            .option-item di atas (bukan di dalamnya) supaya klik ke kolom
                            teks ini tidak ikut memicu onclick="toggleMultipleOption(...)"
                            pada pembungkusnya. Disembunyikan (d-none) sampai checkbox-nya
                            dicentang, lihat toggleMultipleOption() di form-wizard.blade.php.
                        --}}
                        <input type="text"
                            name="question_{{ $question->id }}_other_text"
                            class="form-control form-control-sm mt-2 other-text-input d-none"
                            data-for-option="{{ $option->id }}"
                            placeholder="Tulis jawaban Anda...">
                    @endif
                </div>
            @endforeach
        </div>
    @elseif($question->type === 'dropdown')
        <select class="form-select" name="question_{{ $question->id }}">
            <option value="">-- Select an option --</option>
            @foreach($question->options as $option)
                <option value="{{ $option->id }}">{{ $option->option_text ?: chr(65 + $loop->index) }}</option>
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
    @elseif($question->type === 'file')
        <input type="file"
            name="question_{{ $question->id }}"
            class="form-control"
            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
        <div class="form-text mt-2">
            <i class="bi bi-paperclip"></i>
            Format yang didukung: JPG, JPEG, PNG, atau PDF. Ukuran maksimal 5MB.
        </div>
    @endif

    <div class="error-message">This question is required, please fill in your answer.</div>
</div>

    {{--
        Pertanyaan bercabang: kalau salah satu opsi pertanyaan ini punya
        pertanyaan anak (childQuestions), render sebagai kartu tambahan tepat
        setelah kartu ini — flat sibling, bukan di-nest secara fisik, lihat
        catatan variabel opsional di atas. Cuma single_choice & multiple_choice
        yang bisa jadi pemicu (opsi dropdown/major tidak punya id per-<option>
        yang bisa dicocokkan JS-nya).
    --}}
    @if (in_array($question->type, ['single_choice', 'multiple_choice']))
        @foreach ($question->options as $option)
            @if ($option->childQuestions->isNotEmpty())
                @foreach ($option->childQuestions as $childIndex => $childQuestion)
                    @include('frontend.partials.question-card', [
                        'question' => $childQuestion,
                        'index' => $childIndex,
                        'majors' => $majors ?? collect(),
                        'depth' => $depth + 1,
                        'parentOptionId' => $option->id,
                    ])
                @endforeach
            @endif
        @endforeach
    @endif
@endif
