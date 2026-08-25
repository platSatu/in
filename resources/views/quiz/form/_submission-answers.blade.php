{{--
    Fragmen HTML modal "Hasil" di halaman quiz.form.submissions. Diisi lewat
    fetch() ke route('quiz.form.submissions.answers', $submission->id) begitu
    tombol "Lihat Hasil" per baris peserta diklik — lihat script di bagian
    bawah quiz/form/submissions.blade.php.

    PENTING: file ini SENGAJA tidak @extends layout apa pun (bukan halaman
    penuh, cuma potongan HTML buat disuntik ke modal-body), dan murni
    read-only — tidak ada <form>/tombol aksi di sini sama sekali, sama seperti
    quiz/form/_detail-content.blade.php (tapi ini nampilin JAWABAN ASLI
    peserta, bukan daftar pertanyaan form-nya).

    Blok "Hasil Placement Test" (durasi pengerjaan + rincian per Section/Sub
    Section) HANYA tampil untuk admin di sini -- SENGAJA tidak pernah
    ditampilkan ke peserta/student di sisi manapun, supaya mekanisme
    penilaian tidak bisa dipelajari/dieksploitasi peserta yang mengulang tes.
    Rincian section_breakdown adalah SNAPSHOT yang dibekukan saat hasil
    pertama kali dihitung (lihat migration add_section_breakdown_to_form_
    results_table), jadi tidak berubah walau soal/section-nya diedit belakangan.

    Variabel:
    - $submission     : FormSubmission (with 'student', 'form', 'result')
    - $groupedAnswers : Collection of (object) { question: FormQuestion, rows: Collection<FormAnswer> }
                         sudah diurutkan sesuai FormQuestion::order dan cuma
                         berisi pertanyaan yang benar-benar dijawab.
    - $majors         : Collection<id => name>, buat resolve jawaban type='major'
--}}
<div class="quiz-submission-answers">
    <div class="mb-3 pb-3 border-bottom">
        <div class="fw-bold">
            {{ optional($submission->student)->first_name }} {{ optional($submission->student)->last_name }}
        </div>
        <div class="text-muted small">
            {{ optional($submission->student)->handphone ?? '-' }}
            @if (optional($submission->student)->email)
                &middot; {{ $submission->student->email }}
            @endif
            &middot; Submit: {{ optional($submission->created_at)->format('d M Y, H:i') }}
        </div>
    </div>

    @php
        $quizDurationSeconds = $submission->workDurationSeconds();
        $sectionBreakdown = ($submission->result && $submission->result->mode === 'section_threshold')
            ? $submission->result->section_breakdown
            : null;
    @endphp

    @if ($quizDurationSeconds !== null || $sectionBreakdown)
        <div class="mb-3 pb-3 border-bottom">
            <div class="fw-bold mb-2">Hasil Placement Test</div>

            @if ($quizDurationSeconds !== null)
                <div class="text-muted small mb-2">
                    Durasi pengerjaan: {{ intdiv($quizDurationSeconds, 60) }} menit {{ $quizDurationSeconds % 60 }} detik
                </div>
            @endif

            @if ($sectionBreakdown)
                @foreach ($sectionBreakdown as $sectionRow)
                    <div class="mb-2 p-2 border rounded-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span class="fw-bold">{{ $sectionRow['section_name'] }}</span>
                            <span class="badge {{ $sectionRow['verdict'] === 'stop' ? 'badge-danger' : 'badge-success' }}">
                                {{ $sectionRow['verdict'] === 'stop' ? 'Berhenti di sini' : 'Lolos' }}
                                (Total Salah: {{ $sectionRow['total_wrong'] }})
                            </span>
                        </div>
                        @if (!empty($sectionRow['sub_sections']))
                            <ul class="mb-0 mt-2 ps-3 small text-muted">
                                @foreach ($sectionRow['sub_sections'] as $subSectionRow)
                                    <li>{{ $subSectionRow['name'] }}: {{ $subSectionRow['wrong'] }} salah dari {{ $subSectionRow['total'] }} soal</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
                <div class="text-muted small mb-0">
                    Hasil akhir: <strong>{{ optional($submission->result)->summary_text }}</strong>
                </div>
            @endif
        </div>
    @endif

    @if ($groupedAnswers->isEmpty())
        <p class="text-muted mb-0">Belum ada jawaban tersimpan untuk submission ini.</p>
    @else
        @foreach ($groupedAnswers as $group)
            @php $question = $group->question; @endphp
            <div class="quiz-answer-row mb-3 p-3 border rounded-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="badge bg-secondary">{{ str_replace('_', ' ', $question->type) }}</span>
                </div>
                <div class="fw-bold mb-2">{{ $question->question_text ?: '(tanpa teks — audio/gambar saja)' }}</div>

                <div class="quiz-answer-value">
                    @if ($question->type === 'multiple_choice')
                        <ul class="mb-0 ps-3">
                            @foreach ($group->rows as $row)
                                <li>
                                    @if ($row->option && $row->option->is_other)
                                        {{ $row->answer_text ?: $row->option->option_text }}
                                        <span class="badge bg-light text-dark border">Lainnya</span>
                                    @elseif ($row->option)
                                        @if ($row->option->image)
                                            <img src="{{ asset($row->option->image) }}" alt="" style="max-height: 36px; border-radius: 6px;">
                                        @endif
                                        {{ $row->option->option_text }}
                                    @else
                                        {{ $row->answer_text ?: '-' }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @elseif ($question->type === 'major')
                        @php $row = $group->rows->first(); @endphp
                        {{ $majors[$row->answer_text] ?? ($row->answer_text ?: '-') }}
                    @elseif ($question->type === 'file')
                        @php $row = $group->rows->first(); @endphp
                        @if ($row->answer_text)
                            <a href="{{ asset($row->answer_text) }}" target="_blank" rel="noopener">Lihat File</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    @else
                        @php $row = $group->rows->first(); @endphp
                        @if ($row->option && $row->option->is_other)
                            {{ $row->answer_text ?: $row->option->option_text }}
                            <span class="badge bg-light text-dark border">Lainnya</span>
                        @elseif ($row->option)
                            @if ($row->option->image)
                                <img src="{{ asset($row->option->image) }}" alt="" style="max-height: 36px; border-radius: 6px;">
                            @endif
                            {{ $row->option->option_text }}
                        @else
                            {{ $row->answer_text ?: '-' }}
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
