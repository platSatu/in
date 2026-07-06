@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Package</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('package.create') }}" class="btn btn-primary">+ Add Package</a>
            </div>
        </div>
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
                    <form method="GET" action="{{ route('package.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Search package..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="ecommerce-list" class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th class="checkbox-column">No</th>
                                <th>Package</th>
                                <th>Category</th>
                                <th>Added on</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $package)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>
                                        <div class="d-flex justify-content-left align-items-center">
                                            <div class="avatar me-3">
                                                @if ($package->image)
                                                    <img src="{{ asset('storage/' . $package->image) }}" alt="Package Image" width="64" height="64" style="object-fit: cover;">
                                                @else
                                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                                        {{ strtoupper(substr($package->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="text-truncate fw-bold">{{ $package->name }}</span>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($package->description, 40) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $package->applicationCategory->name ?? '-' }}</td>
                                    <td>{{ optional($package->created_at)->format('Y/m/d') }}</td>
                                    <td>
                                        @if ($package->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format((float) $package->price, 0, ',', '.') }}</td>
                                    <td>{{ $package->duration_days }} Hari</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="#" role="button"
                                               id="dropdownMenuLink{{ $package->id }}"
                                               data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                     class="feather feather-more-horizontal">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="19" cy="12" r="1"></circle>
                                                    <circle cx="5" cy="12" r="1"></circle>
                                                </svg>
                                            </a>

                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $package->id }}">
                                                <a class="dropdown-item" href="{{ route('package.edit', $package->id) }}">Edit</a>

                                                <form action="{{ route('package.destroy', $package->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Hapus package ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada data package.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links() }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
