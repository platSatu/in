@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $data->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('roleuser.create', ['role_id' => $data->id]) }}" class="btn btn-primary">+ Add User to Role</a>
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
            <div class="widget-content widget-content-area br-8 mb-4">
                <h5 class="mb-3">Detail Role</h5>
                <div class="row">
                    <div class="col-sm-4"><strong>Nama</strong></div>
                    <div class="col-sm-8">: {{ $data->name }}</div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><strong>Slug</strong></div>
                    <div class="col-sm-8">: {{ $data->slug }}</div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><strong>Status</strong></div>
                    <div class="col-sm-8">:
                        <span class="badge {{ $data->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                            {{ ucfirst($data->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <h5 class="mb-3">User dengan Role Ini</h5>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge {{ $user->pivot->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($user->pivot->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada user dengan role ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
