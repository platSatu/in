@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <a href="{{ route('quiz.university.create') }}" class="btn btn-primary">+ Add University</a>
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
                    <form method="GET" action="{{ route('quiz.university.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search university/country/city..." value="{{ request('search') }}">
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
                                <th>Name</th>
                                <th>Country</th>
                                <th>City</th>
                                <th>Description</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                      <img src="{{ asset($item->logo) }}" alt="{{ $item->name }}" width="50">
                                    {{ $item->name }}

                                    </td>
                                    <td>{{ $item->country }}</td>
                                    <td>{{ $item->city ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description, 60) ?? '-' }}</td>
                                    <td>{{ optional($item->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="#" role="button"
                                                id="dropdownMenuLink{{ $item->id }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-more-horizontal">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="19" cy="12" r="1"></circle>
                                                    <circle cx="5" cy="12" r="1"></circle>
                                                </svg>
                                            </a>

                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $item->id }}">
                                                <a class="dropdown-item"
                                                    href="{{ route('quiz.university.edit', $item->id) }}">Edit</a>
                                                <a class="dropdown-item"
                                                    href="{{ route('frontend.university.profile', $item->id) }}"
                                                    target="_blank">
                                                        Detail
                                                    </a>

                                                <div class="dropdown-divider"></div>

                                                <a class="dropdown-item"
                                                    href="{{ route('quiz.university-profile.create', ['university_id' => $item->id]) }}">
                                                    + Add Profile
                                                </a>
                                                <a class="dropdown-item"
                                                    href="{{ route('quiz.university-album.create', ['university_id' => $item->id]) }}">
                                                    + Add Album
                                                </a>

                                                <div class="dropdown-divider"></div>

                                                <form action="{{ route('quiz.university.destroy', $item->id) }}"
                                                    method="POST" onsubmit="return confirm('Hapus university ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data university.</td>
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