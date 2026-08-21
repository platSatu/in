@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            @if ($filterForm)
                <h5 class="mb-0">Pertanyaan untuk form: {{ $filterForm->name }}</h5>
                {{-- Sebelumnya link ini ke daftar SEMUA pertanyaan lintas form (bisa
                     ratusan baris, menyulitkan) — sekarang balik ke daftar Form saja,
                     supaya alurnya form -> pertanyaan form itu -> (balik ke form lagi). --}}
                <a href="{{ route('quiz.form.index') }}" class="small">&larr; Kembali ke daftar Form</a>
            @endif
        </div>
        <a href="{{ route('quiz.form-question.create', $filterForm ? ['form_id' => $filterForm->id] : []) }}"
            class="btn btn-primary">+ Add Question</a>
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
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search question/type/status..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Form</th>
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
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                        @if ($item->form_id !== $lastFormId)
                                            {{ optional($item->form)->name ?? '-' }}
                                            @php $lastFormId = $item->form_id; @endphp
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
                                    <td colspan="8" class="text-center">Belum ada data form question.</td>
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

@endsection
