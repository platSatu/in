@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center">
        <h4>Detail Student</h4>

        <a href="{{ route('student.student.index') }}" class="btn btn-secondary">
            Kembali
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

    <div class="widget-content widget-content-area">

        <div class="row g-4">
            <div class="col-md-3 text-center">
                @if($data->images)
                    <img src="{{ asset($data->images) }}" alt="{{ $data->first_name }}" style="width:150px;height:150px;object-fit:cover;border-radius:8px;">
                @else
                    <div class="border rounded d-flex align-items-center justify-content-center text-muted" style="width:150px;height:150px;">
                        No Image
                    </div>
                @endif
            </div>

            <div class="col-md-9">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th style="width:180px;">Nama Lengkap</th>
                        <td>: {{ $data->first_name }} {{ $data->last_name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>: {{ $data->email }}</td>
                    </tr>
                    <tr>
                        <th>Handphone</th>
                        <td>: {{ $data->handphone }}</td>
                    </tr>
                    <tr>
                        <th>Sales</th>
                        <td>: {{ $data->sales->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>:
                            <span class="badge {{ $data->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($data->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <h5>Akun Login</h5>

        @if($data->user)
            <table class="table table-borderless mb-0">
                <tr>
                    <th style="width:180px;">Name</th>
                    <td>: {{ $data->user->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>: {{ $data->user->email }}</td>
                </tr>
                <tr>
                    <th>Handphone</th>
                    <td>: {{ $data->user->handphone }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>: {{ ucfirst($data->user->status) }}</td>
                </tr>
            </table>
        @else
            <p class="text-muted mb-2">Student ini belum memiliki akun login.</p>
            <form action="{{ route('student.student.add-user', $data->id) }}" method="POST" onsubmit="return confirm('Buat akun login untuk {{ $data->first_name }}?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    + Add User
                </button>
            </form>
        @endif

        <div class="d-flex gap-2 mt-4">
            <a href="{{ route('student.student.edit', $data->id) }}" class="btn btn-primary">Edit</a>
        </div>

    </div>

</div>

@endsection
