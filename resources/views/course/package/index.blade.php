@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><span>Course</span></li>
                        <li class="breadcrumb-item active" aria-current="page">Course Package</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('course.package.create') }}" class="btn btn-primary">+ Add Course Package</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('course.package.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search course package..."
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
                                <th>Name</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Level</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th width="180" class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse ($data as $index => $item)

                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>

                                <td class="fw-bold">
                                    {{ $item->name }}
                                </td>

                                <td>{{ optional($item->type)->name ?? '-' }}</td>
                                <td>{{ optional($item->courseClass)->name ?? '-' }}</td>
                                <td>{{ optional($item->level)->name ?? '-' }}</td>

                                <td>{{ $item->duration_value }} {{ ucfirst($item->duration_unit) }}{{ $item->duration_value > 1 ? 's' : '' }}</td>

                                <td>Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>

                                <td>{{ rtrim(rtrim(number_format((float) $item->credits, 2, ',', '.'), '0'), ',') }}</td>

                                <td>
                                    @if ($item->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                        <a href="{{ route('course.package.edit', $item->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            Edit
                                        </a>

                                        <form action="{{ route('course.package.destroy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this course package?')">
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
                                <td colspan="10" class="text-center">
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
