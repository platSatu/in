{{--
    Partial REKURSIF: render satu level daftar pertanyaan (+ opsi jawabannya).
    Dipakai pertama kali oleh _detail-content.blade.php (root questions), lalu
    include ulang dirinya sendiri untuk pertanyaan bercabang/anak (childQuestions
    milik opsi tertentu) — sama pola dengan
    frontend/partials/question-card.blade.php, tapi versi admin/read-only (tanpa
    input apa pun, cuma teks & badge).

    Variabel:
    - $questions : Illuminate\Support\Collection<FormQuestion>
    - $depth      : int, dipakai buat indentasi visual pertanyaan anak
--}}
@php $depth = $depth ?? 0; @endphp

@foreach ($questions as $question)
    <div class="quiz-detail-question-card {{ $depth > 0 ? 'quiz-detail-question-card-nested' : '' }} mb-3 p-3 border rounded-3"
        style="{{ $depth > 0 ? 'margin-left: ' . min($depth, 6) * 20 . 'px; background:#fbfbfd;' : '' }}">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
                <span class="badge bg-secondary">{{ str_replace('_', ' ', $question->type) }}</span>
                @if ($question->required)
                    <span class="badge bg-danger">Wajib</span>
                @endif
                <div class="fw-bold mt-1">
                    {{ $question->question_text ?: '(tanpa teks — audio/gambar saja)' }}
                </div>
            </div>
            <span class="badge {{ $question->status === 'active' ? 'bg-success' : 'bg-secondary' }} text-nowrap">
                {{ ucfirst($question->status) }}
            </span>
        </div>

        @if ($question->description)
            <div class="text-muted small mb-2">{{ $question->description }}</div>
        @endif

        @if ($question->image)
            <div class="mb-2">
                <img src="{{ asset($question->image) }}" alt="" style="max-height: 90px; border-radius: 8px;">
            </div>
        @endif

        @if ($question->audio)
            <div class="mb-2">
                <audio controls preload="none" src="{{ asset($question->audio) }}" style="height: 32px; max-width: 260px;"></audio>
            </div>
        @endif

        {{--
            Sebelumnya list opsi cuma ditampilkan kalau type = single_choice/
            multiple_choice/dropdown — ternyata question type 'text' pun bisa
            punya baris FormQuestionOption tersimpan (tombol "+ Add Options" ada
            di semua type, bukan cuma choice-type), dan itu tidak ikut kelihatan.
            Sekarang: opsi ditampilkan kalau MEMANG ADA datanya, apa pun type-nya.
        --}}
        @if ($question->options->isNotEmpty())
            <ul class="list-group list-group-flush">
                @foreach ($question->options as $option)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 border-0 bg-transparent">
                        <span class="d-flex align-items-center gap-2">
                            @if ($option->image)
                                <img src="{{ asset($option->image) }}" alt="" style="max-height: 36px; border-radius: 6px;">
                            @endif
                            {{ $option->option_text ?: '[Gambar]' }}
                            @if ($option->is_other)
                                <span class="badge bg-light text-dark border">Lainnya</span>
                            @endif
                        </span>
                        @if (!is_null($option->score))
                            <span class="badge bg-light text-dark border">Skor: {{ $option->score }}</span>
                        @endif
                    </li>

                    @if ($option->childQuestions->isNotEmpty())
                        <li class="list-group-item border-0 bg-transparent px-0 py-0">
                            @include('quiz.form._detail-questions', ['questions' => $option->childQuestions, 'depth' => $depth + 1])
                        </li>
                    @endif
                @endforeach
            </ul>
        @elseif ($question->type === 'major')
            <div class="text-muted small fst-italic">Pilihan jurusan diambil otomatis dari data Major.</div>
        @elseif (in_array($question->type, ['single_choice', 'multiple_choice', 'dropdown']))
            <div class="text-muted small fst-italic">Belum ada opsi jawaban.</div>
        @endif
    </div>
@endforeach
