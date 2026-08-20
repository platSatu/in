@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <a href="{{ route('city.create') }}" class="btn btn-primary">+ Add City</a>
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
                    <form method="GET" action="{{ route('city.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search city..."
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
                                <th>Country</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th width="220" class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse($data as $index => $city)

                            <tr>
                                <td>{{ $data->firstItem() + $index }}</td>

                                <td class="fw-bold">
                                    {{ $city->name }}
                                </td>

                                <td>
                                    {{ optional($city->country)->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $city->description ?? '-' }}
                                </td>

                                <td>
                                    {{ optional($city->created_at)->format('Y/m/d') }}
                                </td>

                                <td class="text-center">
                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                        <a href="{{ route('city.show', $city->id) }}" class="btn btn-sm btn-outline-info text-nowrap">
                                            Show
                                        </a>

                                        <a href="{{ route('quiz.major.create', ['city_id' => $city->id]) }}" class="btn btn-sm btn-outline-success text-nowrap">
                                            + Add Major
                                        </a>

                                        <a href="{{ route('city.edit',$city->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            Edit
                                        </a>

                                        <form action="{{ route('city.destroy',$city->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this city?')">
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
