@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Major</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('quiz.major.create') }}" class="btn btn-primary">+ Add Major</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.major.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search major..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2 d-grid">
                            <button class="btn btn-outline-primary">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Major</th>
                                <th>City</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th width="220" class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse ($data as $index => $major)

                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>

                                <td class="fw-bold">
                                    {{ $major->name }}
                                </td>

                                <td>
                                    {{ optional($major->city)->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $major->description ?? '-' }}
                                </td>

                                <td>
                                    {{ optional($major->created_at)->format('Y/m/d') }}
                                </td>

                                <td class="text-center">
                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                        <a href="{{ route('quiz.university.create', ['major_id' => $major->id]) }}" class="btn btn-sm btn-outline-success text-nowrap">
                                            + Add University
                                        </a>

                                        <a href="{{ route('quiz.major.edit',$major->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            Edit
                                        </a>

                                        <form action="{{ route('quiz.major.destroy',$major->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this Major?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger text-nowrap">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="text-center">
                                    No data.
                                </td>
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
