@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">City</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('quiz.major.create') }}" class="btn btn-primary">+ Add City</a>
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
                                <th>City</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th width="100" class="text-center">Action</th>
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
                                    {{ $major->description ?? '-' }}
                                </td>

                                <td>
                                    {{ optional($major->created_at)->format('Y/m/d') }}
                                </td>

                                <td class="text-center">

                                    <div class="dropdown">

                                        <a
                                            class="dropdown-toggle"
                                            href="#"
                                            data-bs-toggle="dropdown">

                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2">

                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="19" cy="12" r="1"></circle>
                                                <circle cx="5" cy="12" r="1"></circle>

                                            </svg>

                                        </a>

                                        <div class="dropdown-menu">

                                            <a
                                                class="dropdown-item"
                                                href="{{ route('quiz.major.edit',$major->id) }}">
                                                Edit
                                            </a>

                                            <form
                                                action="{{ route('quiz.major.destroy',$major->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this Major?')">

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    class="dropdown-item text-danger">
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="text-center">
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