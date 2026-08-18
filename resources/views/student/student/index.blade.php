@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center">
        <h4>Data Student</h4>

        <a href="{{ route('student.student.create') }}" class="btn btn-primary">
            + Add Student
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="widget-content widget-content-area">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1 text-muted">Total Student</p>
                        <h3 class="mb-0">{{ $data->total() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="widget-content widget-content-area">

        <form method="GET" action="{{ route('student.student.index') }}" class="mb-3">
            <div class="input-group" style="max-width: 320px;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari nama/email/handphone..."
                    value="{{ request('search') }}">

                <button class="btn btn-outline-secondary" type="submit">
                    Cari
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Handphone</th>
                        <th>Kode Sales</th>
                        <th>Status</th>
                        <th>Akun Login</th>
                        <th class="text-center" style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>{{ $data->firstItem() + $loop->index }}</td>
                            <td>
                                @if($item->images)
                                    <img src="{{ asset($item->images) }}" alt="{{ $item->first_name }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->handphone }}</td>
                            <td>{{ $item->sales_id ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>
                                @if($item->user_id)
                                    <span class="badge bg-success">Sudah Terdaftar</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Ada</span>
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
                                        <a class="dropdown-item" href="{{ route('student.student.show', $item->id) }}">
                                            Detail
                                        </a>

                                        <a class="dropdown-item" href="{{ route('student.student.edit', $item->id) }}">
                                            Edit
                                        </a>

                                        @unless($item->user_id)
                                            <form action="{{ route('student.student.add-user', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Buat akun login untuk {{ $item->first_name }}?');">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    + Add User
                                                </button>
                                            </form>
                                        @endunless

                                        <form action="{{ route('student.student.destroy', $item->id) }}"
                                            method="POST" onsubmit="return confirm('Hapus data student ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $data->links('pagination::bootstrap-5') }}
        </div>

    </div>

</div>

@endsection
