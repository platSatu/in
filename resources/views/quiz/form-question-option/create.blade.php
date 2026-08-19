@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-question-option.store') }}" method="POST" id="formQuestionOptionCreateForm" enctype="multipart/form-data">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="question_id" class="mb-2">Question</label>

                            @php
                                $lockedQuestionId = old('question_id', $selectedQuestionId ?? null);
                                $lockedQuestion = $lockedQuestionId ? $questions->firstWhere('id', $lockedQuestionId) : null;
                            @endphp

                            @if ($lockedQuestion && !$errors->has('question_id'))
                                <input type="text" class="form-control"
                                    value="{{ \Illuminate\Support\Str::limit($lockedQuestion->question_text, 80) }}" disabled readonly>
                                <input type="hidden" name="question_id" value="{{ $lockedQuestion->id }}">
                                <div class="form-text mb-2">
                                    Semua opsi di bawah ini akan dikaitkan ke pertanyaan di atas.
                                </div>
                                <a href="{{ route('quiz.form-question-option.create') }}" class="btn btn-sm btn-outline-secondary">
                                    Ganti pertanyaan
                                </a>
                            @else
                                <select class="form-select @error('question_id') is-invalid @enderror" id="question_id" name="question_id">
                                    <option value="">Choose question...</option>
                                    @foreach ($questions as $question)
                                        <option value="{{ $question->id }}" {{ $lockedQuestionId == $question->id ? 'selected' : '' }}>
                                            {{ \Illuminate\Support\Str::limit($question->question_text, 80) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('question_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="form-text mb-3">
                        Tambahkan satu atau beberapa opsi jawaban sekaligus. Urutan opsi yang ditampilkan nanti akan
                        sama persis dengan urutan baris di bawah ini (dari atas ke bawah). Tiap opsi boleh teks saja,
                        gambar saja, atau dua-duanya — minimal salah satu harus diisi (mis. soal Listening yang
                        jawabannya berupa gambar tanpa teks).
                    </div>

                    @error('options')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div id="optionRowsContainer"></div>

                    <button type="button" id="addOptionRowBtn" class="btn btn-outline-primary btn-sm mb-4">
                        + Tambah Baris
                    </button>

                    <div class="row">
                        <div class="col-sm-3 mb-3">
                            <button type="submit" class="btn btn-success w-100">Simpan Semua Option</button>
                        </div>
                        <div class="col-sm-3 mb-3">
                            <a href="{{ route('quiz.form-question-option.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

</div>

{{-- Template satu baris option, di-clone lewat JS --}}
<template id="optionRowTemplate">
    <div class="option-row border rounded-3 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold row-number">Option #1</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus baris">
                &times; Hapus
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small">Option Text <span class="text-muted">(opsional kalau sudah ada gambar)</span></label>
                <input type="text" class="form-control" name="__NAME__[option_text]" placeholder="Enter option text...">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Score <span class="text-muted">(Optional)</span></label>
                <input type="number" class="form-control" name="__NAME__[score]">
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label small">Gambar Opsi <span class="text-muted">(opsional, maks 4MB)</span></label>
                <input type="file" class="form-control form-control-sm" name="__NAME__[image]" accept="image/*">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="__NAME__[status]">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="form-text mt-2">
            Score dipakai kalau form ini mengaktifkan Mode Hasil "Otomatis" — skor opsi yang dipilih peserta akan
            dijumlahkan jadi hasil akhir. Kosongkan/biarkan 0 kalau opsi ini tidak berkontribusi ke skor.
        </div>
    </div>
</template>

<script>
    (function () {
        const container = document.getElementById('optionRowsContainer');
        const template = document.getElementById('optionRowTemplate');
        const addBtn = document.getElementById('addOptionRowBtn');
        let rowCounter = 0;

        function renumberRows() {
            const rows = container.querySelectorAll('.option-row');
            rows.forEach(function (row, index) {
                row.querySelector('.row-number').textContent = 'Option #' + (index + 1);
            });

            // Tombol hapus dinonaktifkan kalau tinggal 1 baris (minimal harus ada 1 option).
            const removeButtons = container.querySelectorAll('.remove-row-btn');
            removeButtons.forEach(function (btn) {
                btn.disabled = rows.length <= 1;
            });
        }

        function addRow() {
            const fragment = template.content.cloneNode(true);
            const name = 'options[' + rowCounter + ']';
            rowCounter++;

            fragment.querySelectorAll('[name]').forEach(function (el) {
                el.setAttribute('name', el.getAttribute('name').replace('__NAME__', name));
            });

            const row = fragment.querySelector('.option-row');
            row.querySelector('.remove-row-btn').addEventListener('click', function () {
                if (container.querySelectorAll('.option-row').length > 1) {
                    row.remove();
                    renumberRows();
                }
            });

            container.appendChild(fragment);
            renumberRows();
        }

        addBtn.addEventListener('click', addRow);

        // Mulai dengan 1 baris kosong.
        addRow();
    })();
</script>

@endsection
