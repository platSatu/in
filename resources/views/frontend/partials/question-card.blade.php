{{--
    Partial kartu pertanyaan, dipakai bareng oleh step "Data Pribadi" dan step
    "Placement Test" di form-wizard.blade.php (sebelumnya block ini cuma ada
    sekali, inline, di dalam #step-questions). Variabel yang dibutuhkan:
    - $question : App\Models\FormQuestion (with('options'))
    - $index    : int, index dalam loop (dipakai untuk nomor urut & huruf opsi)
    - $majors   : Illuminate\Support\Collection, hanya dipakai untuk type 'major'
--}}
<div class="question-card" data-required="{{ $question->required ? '1' : '0' }}" data-question-id="{{ $question->id }}">
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
                                class="form-check-input"
                                id="option_{{ $option->id }}">
                            <label class="form-check-label option-content mb-0" for="option_{{ $option->id }}">
                                {{ $option->option_text ?: chr(65 + $loop->index) }}
                            </label>
                        </div>
                    </div>
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
    @endif

    <div class="error-message">This question is required, please fill in your answer.</div>
</div>
