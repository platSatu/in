@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('company.division.index') }}">Company Division</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $data->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('company.division.addUser', $data->id) }}" class="btn btn-primary">+ Add User</a>
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
                <h5 class="mb-3">Detail Divisi</h5>
                <div class="row">
                    <div class="col-sm-4"><strong>Nama</strong></div>
                    <div class="col-sm-8">: {{ $data->name }}</div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><strong>Company Branch</strong></div>
                    <div class="col-sm-8">: {{ optional($data->companyBranch)->name ?? '-' }}</div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><strong>Deskripsi</strong></div>
                    <div class="col-sm-8">: {{ $data->description ?? '-' }}</div>
                </div>
                <div class="row">
                    <div class="col-sm-4"><strong>Status</strong></div>
                    <div class="col-sm-8">:
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

                <h5 class="mb-3">User dalam Divisi Ini</h5>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data->divisionUsers as $index => $divisionUser)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold">{{ optional($divisionUser->user)->name ?? '-' }}</td>
                                    <td>{{ optional($divisionUser->user)->email ?? '-' }}</td>
                                    <td>
                                        @if ($divisionUser->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            @if ($divisionUser->user)
                                                <a href="{{ route('roleuser.create', ['user_id' => $divisionUser->user->id]) }}"
                                                    class="btn btn-sm btn-outline-primary text-nowrap">+ Add User to Role</a>
                                            @endif

                                            <form action="{{ route('company.division.removeUser', [$data->id, $divisionUser->id]) }}"
                                                method="POST" onsubmit="return confirm('Keluarkan user ini dari divisi?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger text-nowrap">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada user dalam divisi ini.</td>
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
