@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-question.store') }}" method="POST" id="formQuestionCreateForm" enctype="multipart/form-data">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="form_id" class="mb-2">Form</label>

                            @php
                                $lockedFormId = old('form_id', $selectedFormId ?? null);
                                $lockedForm = $lockedFormId ? $forms->firstWhere('id', $lockedFormId) : null;
                            @endphp

                            @if ($lockedForm && !$errors->has('form_id'))
                                <input type="text" class="form-control" value="{{ $lockedForm->name }}" disabled readonly>
                                <input type="hidden" name="form_id" value="{{ $lockedForm->id }}">
                                <div class="form-text mb-2">
                                    Semua pertanyaan di bawah ini akan dikaitkan ke form di atas.
                                </div>
                                <a href="{{ route('quiz.form-question.create') }}" class="btn btn-sm btn-outline-secondary">
                                    Ganti form
                                </a>
                            @else
                                <select class="form-select @error('form_id') is-invalid @enderror" id="form_id" name="form_id">
                                    <option value="">Choose form...</option>
                                    @foreach ($forms as $form)
                                        <option value="{{ $form->id }}" {{ $lockedFormId == $form->id ? 'selected' : '' }}>
                                            {{ $form->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('form_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="form-text mb-3">
                        Tambahkan satu atau beberapa pertanyaan sekaligus. Urutan pertanyaan yang ditampilkan nanti
                        akan sama persis dengan urutan baris di bawah ini (dari atas ke bawah). Tiap pertanyaan boleh
                        kombinasi bebas teks / audio / gambar (mis. soal Listening: audio + gambar tanpa teks sama
                        sekali) — minimal salah satu dari ketiganya harus diisi.
                    </div>

                    @error('questions')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div id="questionRowsContainer"></div>

                    <button type="button" id="addQuestionRowBtn" class="btn btn-outline-primary btn-sm mb-4">
                        + Tambah Baris
                    </button>

                    <div class="row">
                        <div class="col-sm-3 mb-3">
                            <button type="submit" class="btn btn-success w-100">Simpan Semua Pertanyaan</button>
                        </div>
                        <div class="col-sm-3 mb-3">
                            <a href="{{ route('quiz.form-question.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

</div>

{{-- Template satu baris pertanyaan, di-clone lewat JS --}}
<template id="questionRowTemplate">
    <div class="question-row border rounded-3 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold row-number">Pertanyaan #1</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus baris">
                &times; Hapus
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small">Question Text <span class="text-muted">(opsional kalau sudah ada audio/gambar)</span></label>
                <textarea class="form-control" name="__NAME__[question_text]" rows="2" placeholder="Tulis pertanyaan..."></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Type</label>
                <select class="form-select" name="__NAME__[type]" required>
                    <option value="">Choose...</option>
                    <option value="text">Text (single line)</option>
                    <option value="textarea">Textarea (long text)</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="single_choice">Single Choice</option>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="major">Major</option>
                    <option value="file">File Upload (jpg/jpeg/png/pdf)</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label small">Termasuk Step <span class="text-muted">(dipakai kalau form ini mengaktifkan step "Data Pribadi")</span></label>
                <select class="form-select" name="__NAME__[stage_group]">
                    <option value="placement_test" selected>Placement Test</option>
                    <option value="personal_data">Data Pribadi</option>
                </select>
            </div>
            @if ($lockedForm)
                <div class="col-md-6">
                    <label class="form-label small">
                        Tampilkan hanya jika opsi ini dipilih
                        <span class="text-muted">(opsional — pertanyaan bercabang)</span>
                    </label>
                    <select class="form-select" name="__NAME__[parent_option_id]">
                        <option value="">-- Tidak ada (pertanyaan utama) --</option>
                        @foreach ($parentOptionChoices as $opt)
                            <option value="{{ $opt->id }}">
                                {{ \Illuminate\Support\Str::limit(optional($opt->question)->question_text ?: 'Pertanyaan', 40) }}
                                &rarr; {{ $opt->option_text ?: '[Gambar]' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($parentOptionChoices->isEmpty())
                        <div class="form-text">
                            Belum ada opsi tersimpan di form ini. Simpan dulu pertanyaan single/multiple
                            choice beserta opsinya, baru pertanyaan berikutnya bisa dijadikan cabang dari
                            salah satu opsi tersebut.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <label class="form-label small">Description <span class="text-muted">(opsional, instruksi tambahan mis. "Pilih yang sesuai:")</span></label>
                <input type="text" class="form-control" name="__NAME__[description]" placeholder="Instruksi tambahan...">
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label small">Audio <span class="text-muted">(opsional, mp3/wav/ogg/m4a, maks 8MB)</span></label>
                <input type="file" class="form-control form-control-sm" name="__NAME__[audio]" accept="audio/*">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Gambar Pertanyaan <span class="text-muted">(opsional, maks 4MB)</span></label>
                <input type="file" class="form-control form-control-sm" name="__NAME__[image]" accept="image/*">
            </div>
        </div>

        <div class="row g-3 mt-1 align-items-center">
            <div class="col-md-3">
                <label class="form-label small d-block">Required</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="__NAME__[required]" value="1" checked>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="__NAME__[status]">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
    (function () {
        const container = document.getElementById('questionRowsContainer');
        const template = document.getElementById('questionRowTemplate');
        const addBtn = document.getElementById('addQuestionRowBtn');
        let rowCounter = 0;

        function renumberRows() {
            const rows = container.querySelectorAll('.question-row');
            rows.forEach(function (row, index) {
                row.querySelector('.row-number').textContent = 'Pertanyaan #' + (index + 1);
            });

            // Tombol hapus dinonaktifkan kalau tinggal 1 baris (minimal harus ada 1 pertanyaan).
            const removeButtons = container.querySelectorAll('.remove-row-btn');
            removeButtons.forEach(function (btn) {
                btn.disabled = rows.length <= 1;
            });
        }

        function addRow() {
            const fragment = template.content.cloneNode(true);
            const name = 'questions[' + rowCounter + ']';
            rowCounter++;

            fragment.querySelectorAll('[name]').forEach(function (el) {
                el.setAttribute('name', el.getAttribute('name').replace('__NAME__', name));
            });

            const row = fragment.querySelector('.question-row');
            row.querySelector('.remove-row-btn').addEventListener('click', function () {
                if (container.querySelectorAll('.question-row').length > 1) {
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
