@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Role User</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('roleuser.create') }}" class="btn btn-primary">+ Add Role User</a>
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
                    <form method="GET" action="{{ route('roleuser.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Search role user..."
                                   value="{{ request('search') }}">
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
                                <th>User</th>
                                <th>Role</th>
                                <th>Scope</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ optional($item->user)->name ?? '-' }}</td>
                                    <td>{{ optional($item->role)->name ?? '-' }}</td>
                                    <td>
                                        @if (optional($item->role)->scope_level === 'branch')
                                            Branch: {{ optional($item->companyBranch)->name ?? '-' }}
                                        @elseif (optional($item->role)->scope_level === 'division')
                                            Division: {{ optional($item->companyDivision)->name ?? '-' }}
                                        @else
                                            {{ ucfirst(optional($item->role)->scope_level ?? '-') }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('roleuser.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <form action="{{ route('roleuser.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus data role user ini?');" class="m-0">
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
                                    <td colspan="6" class="text-center">Belum ada data role user.</td>
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
