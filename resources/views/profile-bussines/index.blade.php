@extends('layouts.frontend')
@section('content')

<div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
    <div class="widget-content widget-content-area br-8">
        
        <!-- Header & Search -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Profile Bussines</h4>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary ms-2">Cari</button>
                </form>
                <a href="{{ route('profile-bussines.create') }}" class="btn btn-success">+ Add New</a>
            </div>
        </div>

        <!-- Alert Success -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Table -->
        <table id="profile-bussines-list" class="table dt-table-hover" style="width:100%">
            <thead>
                <tr>
                    <th class="checkbox-column">
                        <div class="form-check custom-checkbox mb-2">
                            <input type="checkbox" class="form-check-input" id="customCheck1">
                        </div>
                    </th>
                    <th>Business Name</th>
                    <th>Added on</th>
                    <th>Status</th>
                    <th>Owner</th>
                    <th class="no-content text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                        <td>
                            <div class="d-flex justify-content-left align-items-center">
                                <div class="avatar me-3">
                                    <img src="{{ asset('src/assets/img/product-3.jpg') }}" alt="Avatar" width="64" height="64" class="rounded">
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-truncate fw-bold">{{ $row->name }}</span>
                                    <small class="text-muted">{{ Str::limit($row->description, 30) }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $row->created_at->format('Y/m/d') }}</td>
                        <td>
                            @if($row->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $row->user->name ?? '-' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink{{ $row->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                </a>

                                <div class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $row->id }}">
                                    <a class="dropdown-item" href="{{ route('profile-bussines.show', $row->id) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye me-2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        View
                                    </a>
                                    <a class="dropdown-item" href="{{ route('profile-bussines.edit', $row->id) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit me-2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('profile-bussines.destroy', $row->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Yakin hapus?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash me-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() }} entries
            </div>
            <div>
                {{ $data->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>
</div>

@endsection