@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            @if ($filterSection)
                {{-- Datang dari tombol "Lihat Soal"/"+ Add Question" di
                     quiz/form-section/index.blade.php -> lebih spesifik dari
                     $filterForm biasa, jadi diprioritaskan duluan. Balik ke daftar
                     Section (form yang sama), bukan ke daftar Form, supaya alurnya
                     kebaca: Form -> Section -> Pertanyaan section itu -> (balik ke
                     Section lagi). --}}
                <h5 class="mb-0">Pertanyaan section: {{ $filterSection->name }}</h5>
                <small class="text-muted d-block mb-1">Form: {{ optional($filterForm)->name ?? '-' }}</small>
                <a href="{{ route('quiz.form-section.index', ['form_id' => $filterSection->form_id]) }}" class="small">&larr; Kembali ke daftar Section</a>
            @elseif ($filterForm)
                <h5 class="mb-0">Pertanyaan untuk form: {{ $filterForm->name }}</h5>
                {{-- Sebelumnya link ini ke daftar SEMUA pertanyaan lintas form (bisa
                     ratusan baris, menyulitkan) — sekarang balik ke daftar Form saja,
                     supaya alurnya form -> pertanyaan form itu -> (balik ke form lagi). --}}
                <a href="{{ route('quiz.form.index') }}" class="small">&larr; Kembali ke daftar Form</a>
            @endif
        </div>
        <a href="{{ route('quiz.form-question.create', array_filter([
                'form_id' => optional($filterForm)->id ?? optional($filterSection)->form_id,
                'section_id' => optional($filterSection)->id,
            ])) }}" class="btn btn-primary">+ Add Question</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.form-question.index') }}" class="row g-2">
                        @if ($filterForm)
                            <input type="hidden" name="form_id" value="{{ $filterForm->id }}">
                        @endif
                        @if ($filterSection)
                            <input type="hidden" name="section_id" value="{{ $filterSection->id }}">
                        @endif
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search question/type/status..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                @if ($filterForm)
                    {{-- Bulk assign section: cuma ditampilkan kalau halaman ini sedang
                         difilter ke SATU form spesifik ($filterForm) — memindahkan
                         section lintas form tidak masuk akal (section selalu milik 1
                         form), jadi fitur ini sengaja tidak muncul di daftar "semua
                         pertanyaan" yang belum terfilter. Form ini TERPISAH dari
                         form-form per-baris (Delete) di bawah supaya tidak ada form
                         yang nge-nest di dalam form lain (HTML tidak mengizinkan
                         <form> bersarang) — checkbox di tabel bukan bagian dari form
                         ini, nilainya disuntikkan lewat JS sesaat sebelum submit
                         (lihat script di bawah). --}}
                    <form id="bulkAssignForm" method="POST"
                        action="{{ route('quiz.form-question.bulk-assign-section') }}"
                        class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 border rounded">
                        @csrf
                        <input type="hidden" name="form_id" value="{{ $filterForm->id }}">
                        <span id="bulkAssignCount" class="text-muted small">0 pertanyaan dipilih</span>
                        <select name="section_id" class="form-select form-select-sm" style="max-width: 240px;">
                            <option value="">-- Lepas dari section --</option>
                            @foreach ($bulkAssignSectionChoices as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary" id="bulkAssignSubmit" disabled>
                            Pindahkan ke Section
                        </button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                @if ($filterForm)
                                    <th class="text-center">
                                        <input type="checkbox" id="selectAllQuestions" title="Pilih semua">
                                    </th>
                                @endif
                                <th>No</th>
                                <th>Form</th>
                                <th>Section</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $lastFormId = null; @endphp
                            @forelse ($data as $index => $item)
                                <tr>
                                    @if ($filterForm)
                                        <td class="text-center">
                                            <input type="checkbox" class="question-checkbox" value="{{ $item->id }}">
                                        </td>
                                    @endif
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                        @if ($item->form_id !== $lastFormId)
                                            {{ optional($item->form)->name ?? '-' }}
                                            @php $lastFormId = $item->form_id; @endphp
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->section)
                                            <span class="badge badge-secondary text-nowrap">{{ $item->section->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->question_text }}</td>
                                    <td>{{ str_replace('_', ' ', $item->type) }}</td>
                                    <td>{{ $item->order }}</td>
                                    <td>
                                        @if ($item->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            {{-- Show: lihat semua jawaban/opsi pertanyaan ini (pakai kembali halaman
                                                 index Form Question Option, difilter question_id) — dari situ juga
                                                 ada tombol Add Jawaban (question_id sudah terisi) dan Edit per baris,
                                                 supaya tidak perlu bolak-balik buka menu Form Question Option dari awal. --}}
                                            <a href="{{ route('quiz.form-question-option.index', ['question_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap">Show Options</a>

                                            <a href="{{ route('quiz.form-question.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <a href="{{ route('quiz.form-question-option.create', ['question_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Add Options</a>

                                            <form action="{{ route('quiz.form-question.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus question ini?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $filterForm ? 10 : 9 }}" class="text-center">Belum ada data form question.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

@if ($filterForm)
    <script>
        // Bulk assign section: checkbox per baris TIDAK berada di dalam
        // #bulkAssignForm (lihat komentar di atas tabel — form tidak boleh
        // bersarang di dalam <form> Delete per baris). Sesaat sebelum submit,
        // JS di sini menyuntikkan nilai checkbox yang tercentang sebagai
        // hidden input question_ids[] ke dalam #bulkAssignForm.
        document.addEventListener('DOMContentLoaded', function () {
            var selectAll = document.getElementById('selectAllQuestions');
            var checkboxes = document.querySelectorAll('.question-checkbox');
            var bulkForm = document.getElementById('bulkAssignForm');
            var countLabel = document.getElementById('bulkAssignCount');
            var submitBtn = document.getElementById('bulkAssignSubmit');

            function updateCount() {
                var checkedCount = document.querySelectorAll('.question-checkbox:checked').length;
                countLabel.textContent = checkedCount + ' pertanyaan dipilih';
                submitBtn.disabled = checkedCount === 0;

                if (selectAll) {
                    selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    updateCount();
                });
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateCount);
            });

            if (bulkForm) {
                bulkForm.addEventListener('submit', function (event) {
                    var checked = document.querySelectorAll('.question-checkbox:checked');

                    if (checked.length === 0) {
                        event.preventDefault();
                        return;
                    }

                    var targetLabel = bulkForm.querySelector('select[name="section_id"]').selectedOptions[0].text;
                    if (!confirm('Pindahkan ' + checked.length + ' pertanyaan ke "' + targetLabel + '"?')) {
                        event.preventDefault();
                        return;
                    }

                    // Buang hidden input question_ids lama dulu (jaga-jaga kalau
                    // browser mengembalikan form ini dari cache back/forward
                    // dengan hidden input yang sempat disuntik sebelumnya).
                    bulkForm.querySelectorAll('input[name="question_ids[]"]').forEach(function (el) {
                        el.remove();
                    });

                    checked.forEach(function (checkbox) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'question_ids[]';
                        hidden.value = checkbox.value;
                        bulkForm.appendChild(hidden);
                    });
                });
            }

            updateCount();
        });
    </script>
@endif

@endsection
