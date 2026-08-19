@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">Data Student</h4>

        <a href="{{ route('student.student.create') }}" class="btn btn-primary">
            + Add Student
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- === RINGKASAN === --}}
    <div class="row layout-top-spacing g-3 mb-1">
        <div class="col-md-4 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #22c55e;">
                <div class="fs-4 fw-bold text-success">{{ $data->total() }}</div>
                <div class="text-muted small">Total Student</div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #f59e0b;">
                <div class="fs-4 fw-bold text-warning">{{ $totalBranches }}</div>
                <div class="text-muted small">Total Branch</div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #6259ca;">
                <div class="fs-4 fw-bold text-primary">{{ $totalForms }}</div>
                <div class="text-muted small">Total Form</div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('student.student.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Cari</label>
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Cari nama/email/handphone..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- Semua Branch --</option>
                                @foreach ($companyBranches as $companyBranch)
                                    <option value="{{ $companyBranch->id }}" {{ $branchId == $companyBranch->id ? 'selected' : '' }}>
                                        {{ $companyBranch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Form</label>
                            <select name="form_id" class="form-select">
                                <option value="">-- Semua Form --</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}" {{ $formId == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <div class="w-100">
                                <button class="btn btn-outline-primary w-100" type="submit">
                                    Filter
                                </button>
                            </div>

                            @if(request('search') || request('branch_id') || request('form_id'))
                                <a href="{{ route('student.student.index') }}" class="btn btn-outline-danger" title="Reset filter">
                                    &times;
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th class="text-nowrap">Handphone</th>
                                <th class="text-nowrap">Branch</th>
                                <th>Form</th>
                                <th class="text-nowrap">Kode Sales</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Akun Login</th>
                                <th class="no-content text-center">Action</th>
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
                                    <td class="fw-bold">{{ $item->first_name }} {{ $item->last_name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td class="text-nowrap">{{ $item->handphone }}</td>
                                    <td class="text-nowrap">{{ $item->companyBranch->name ?? '-' }}</td>
                                    <td>{{ $item->form->name ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $item->sales_id ?? '-' }}</td>
                                    <td class="text-nowrap">
                                        <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        @if($item->user_id)
                                            <span class="badge bg-success">Sudah Terdaftar</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('student.student.show', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap">Detail</a>

                                            <a href="{{ route('student.student.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            @unless($item->user_id)
                                                <form action="{{ route('student.student.add-user', $item->id) }}"
                                                    method="POST" onsubmit="return confirm('Buat akun login untuk {{ $item->first_name }}?');" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">+ User</button>
                                                </form>
                                            @endunless

                                            <form action="{{ route('student.student.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus data student ini?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Belum ada data.</td>
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
