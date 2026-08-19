@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Company Branch</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('company.branch.create') }}" class="btn btn-primary">+ Add Branch</a>
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
                    <form method="GET" action="{{ route('company.branch.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search name/address/email..." value="{{ request('search') }}">
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
                                <th>Company Profile</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ optional($item->companyProfile)->name ?? '-' }}</td>
                                    <td class="fw-bold">{{ $item->name }}</td>
                                    <td>{{ $item->address ?? '-' }}</td>
                                    <td>
                                        {{ $item->handphone ?? '-' }}<br>
                                        {{ $item->email ?? '-' }}
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
                                            <a href="{{ route('company.branch.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <a href="{{ route('company.division.create', ['company_branch_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Add Divisi</a>

                                            <a href="{{ route('quiz.form.create', ['company_branch_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-info text-nowrap">+ Add Form</a>

                                            <form action="{{ route('company.branch.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus company branch ini?');" class="m-0">
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
                                    <td colspan="8" class="text-center">Belum ada data company branch.</td>
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
