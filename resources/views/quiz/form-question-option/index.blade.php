@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            @if ($filterQuestion)
                <h5 class="mb-0">Jawaban untuk pertanyaan: {{ \Illuminate\Support\Str::limit($filterQuestion->question_text, 60) }}</h5>
                <a href="{{ route('quiz.form-question-option.index') }}" class="small">&larr; Lihat semua jawaban (semua pertanyaan)</a>
            @endif
        </div>
        <a href="{{ route('quiz.form-question-option.create', $filterQuestion ? ['question_id' => $filterQuestion->id] : []) }}"
            class="btn btn-primary">+ Add Option</a>
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
                    <form method="GET" action="{{ route('quiz.form-question-option.index') }}" class="row g-2">
                        @if ($filterQuestion)
                            <input type="hidden" name="question_id" value="{{ $filterQuestion->id }}">
                        @endif
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search option/status..." value="{{ request('search') }}">
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
                                <th>Question</th>
                                <th>Gambar</th>
                                <th>Option Text</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit(optional($item->question)->question_text, 50) ?? '-' }}</td>
                                    <td>
                                        @if ($item->image)
                                            <img src="{{ asset($item->image) }}" alt=""
                                                style="width:48px; height:48px; object-fit:cover; border-radius:6px;">
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="fw-bold">{{ $item->option_text ?? '-' }}</td>
                                    <td>{{ $item->score ?? '-' }}</td>
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
                                            <a href="{{ route('quiz.form-question-option.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <form action="{{ route('quiz.form-question-option.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus option ini?');" class="m-0">
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
                                    <td colspan="8" class="text-center">Belum ada data form question option.</td>
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
