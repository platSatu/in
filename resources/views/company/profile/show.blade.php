@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('company.profile.index') }}">Company Profile</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $data->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('company.branch.create', ['company_profile_id' => $data->id]) }}" class="btn btn-success me-2">+ Add Branch</a>
                <a href="{{ route('company.profile.index') }}" class="btn btn-outline-secondary">Back</a>
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
                <div class="d-flex align-items-center mb-4">
                    <img src="{{ asset($data->logo) }}" alt="{{ $data->name }}" width="56" height="56" class="rounded me-3" style="object-fit:cover;">
                    <div>
                        <h5 class="mb-1">{{ $data->name }}</h5>
                        <div class="text-muted small">
                            {{ $data->address ?? '-' }}
                            @if ($data->handphone) &middot; {{ $data->handphone }} @endif
                            @if ($data->email) &middot; {{ $data->email }} @endif
                        </div>
                    </div>
                    <div class="ms-auto">
                        @if ($data->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <h6 class="mb-3">Branch ({{ $data->branches->count() }})</h6>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data->branches as $index => $branch)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold">{{ $branch->name }}</td>
                                    <td>{{ $branch->address ?? '-' }}</td>
                                    <td>
                                        {{ $branch->handphone ?? '-' }}<br>
                                        {{ $branch->email ?? '-' }}
                                    </td>
                                    <td>
                                        @if ($branch->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($branch->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('company.branch.edit', $branch->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <form action="{{ route('company.branch.destroy', $branch->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus branch ini?');" class="m-0">
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
                                    <td colspan="7" class="text-center">Company profile ini belum punya branch.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
