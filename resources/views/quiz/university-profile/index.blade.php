@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        {{-- Kalau sedang difilter per kampus, kampusnya ikut terkunci di form create. --}}
        <a href="{{ route('quiz.university-profile.create', $filterUniversity ? ['university_id' => $filterUniversity->id] : []) }}" class="btn btn-primary">+ Add University Profile</a>
    </div>

    @if ($filterUniversity)
        @php $filteredTotal = $data->total(); @endphp
        <div class="alert {{ $filteredTotal > 1 ? 'alert-warning' : 'alert-info' }} d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                Profile untuk: <strong>{{ $filterUniversity->name }}</strong>
                ({{ $filteredTotal }} profile{{ request('search') ? ' sesuai pencarian' : '' }})
                @if ($filteredTotal > 1 && ! request('search'))
                    <div class="small">Satu kampus seharusnya cukup 1 profile. Cek lalu hapus profile yang dobel.</div>
                @endif
            </div>
            <a href="{{ route('quiz.university-profile.index') }}" class="btn btn-sm btn-outline-secondary">Tampilkan semua</a>
        </div>
    @endif

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
                    <form method="GET" action="{{ route('quiz.university-profile.index') }}" class="row g-2">
                        @if ($filterUniversity)
                            <input type="hidden" name="university_id" value="{{ $filterUniversity->id }}">
                        @endif
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search university/field/language/status..." value="{{ request('search') }}">
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
                                <th>University</th>
                                <th>Field</th>
                                <th>Budget</th>
                                <th>Language</th>
                                <th>Scholarship</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ optional($item->university)->name ?? '-' }}</td>
                                    <td>{{ $item->field }}</td>
                                    <td>
                                        @if ($item->min_budget !== null || $item->max_budget !== null)
                                            {{ number_format((int) ($item->min_budget ?? 0)) }} - {{ number_format((int) ($item->max_budget ?? 0)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $item->language ?? '-' }}</td>
                                    <td>
                                        @if ((bool) $item->scholarship_available)
                                            <span class="badge badge-success">Available</span>
                                        @else
                                            <span class="badge badge-danger">Not Available</span>
                                        @endif
                                    </td>
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
                                            <a href="{{ route('quiz.university-profile.edit', $item->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                                Edit
                                            </a>

                                            <a href="{{ route('quiz.university-album.create', ['university_id' => $item->university_id]) }}" class="btn btn-sm btn-outline-success text-nowrap">
                                                + Add Category Album
                                            </a>

                                            <form action="{{ route('quiz.university-profile.destroy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus university profile ini?');">
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
                                    <td colspan="9" class="text-center">Belum ada data university profile.</td>
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
