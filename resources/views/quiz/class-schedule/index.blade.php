@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Class Schedule</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('quiz.class-schedule.create') }}" class="btn btn-primary">+ Add Class Schedule</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @error('delete')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @enderror

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.class-schedule.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search nama kelas/level..." value="{{ request('search') }}">
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
                                <th>Branch</th>
                                <th>Nama Kelas</th>
                                <th>Level</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Kuota</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ $item->companyBranch->name ?? '-' }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->level ?: '-' }}</td>
                                    <td>{{ optional($item->class_date)->format('d/m/Y') }}</td>
                                    <td>{{ $item->start_time ? substr($item->start_time, 0, 5) : '-' }}</td>
                                    <td>{{ $item->active_enrollments_count }} / {{ $item->capacity }}</td>
                                    <td>
                                        @if ($item->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
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
                                                    href="{{ route('quiz.class-schedule.participants', $item->id) }}">Peserta</a>

                                                <a class="dropdown-item"
                                                    href="{{ route('quiz.class-schedule.edit', $item->id) }}">Edit</a>

                                                <form action="{{ route('quiz.class-schedule.destroy', $item->id) }}"
                                                    method="POST" onsubmit="return confirm('Hapus jadwal kelas ini?');">
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
                                    <td colspan="9" class="text-center">Belum ada data jadwal kelas.</td>
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
