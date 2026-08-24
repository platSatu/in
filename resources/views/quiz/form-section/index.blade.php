@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            @if ($filterForm)
                <h5 class="mb-0">Section untuk form: {{ $filterForm->name }}</h5>
                <a href="{{ route('quiz.form.index') }}" class="small">&larr; Kembali ke daftar Form</a>
            @endif
        </div>
        <a href="{{ route('quiz.form-section.create', $filterForm ? ['form_id' => $filterForm->id] : []) }}"
            class="btn btn-primary">+ Add Section</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->has('delete'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('delete') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.form-section.index') }}" class="row g-2">
                        @if ($filterForm)
                            <input type="hidden" name="form_id" value="{{ $filterForm->id }}">
                        @endif
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search section name/status..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="form-text mb-3">
                    Section dipakai untuk membungkus beberapa pertanyaan Placement Test jadi satu kelompok (mis.
                    "HSK 1 - Section A") yang ditampilkan satu per satu ke peserta. Sifatnya opsional — pertanyaan
                    yang belum dimasukkan ke section mana pun tetap tampil seperti biasa.
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Form</th>
                                <th>Nama Section</th>
                                <th>Parent Section</th>
                                <th>Description</th>
                                <th>Urutan</th>
                                <th>Jumlah Soal</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ optional($item->form)->name ?? '-' }}</td>
                                    <td class="fw-bold">
                                        {{ $item->parent_section_id ? '↳ ' : '' }}{{ $item->name }}
                                    </td>
                                    <td>{{ optional($item->parentSection)->name ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description, 60) ?: '-' }}</td>
                                    <td>{{ $item->order }}</td>
                                    <td>{{ $item->questions_count }}</td>
                                    <td>
                                        @if ($item->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            {{-- Dulu link ini menampilkan SEMUA soal form (tidak terfilter
                                                 section), sekarang sekalian dikirim section_id-nya supaya
                                                 langsung terfilter cuma soal section ini saja — lihat
                                                 penyesuaian $filterSection di FormQuestionController::index(). --}}
                                            <a href="{{ route('quiz.form-question.create', ['form_id' => $item->form_id, 'section_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Add Question</a>

                                            <a href="{{ route('quiz.form-question.index', ['form_id' => $item->form_id, 'section_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap">Lihat Soal</a>
                                            <a href="{{ route('quiz.form-section.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <form action="{{ route('quiz.form-section.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus section ini?');" class="m-0">
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
                                    <td colspan="9" class="text-center">Belum ada section.</td>
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
