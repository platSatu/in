@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">Edit Student</h4>
        <a href="{{ route('student.student.index') }}" class="btn btn-outline-secondary">&larr; Kembali ke daftar student</a>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <form action="{{ route('student.student.update', $data->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Foto</label><br>
                        @if($data->images)
                            <img src="{{ asset($data->images) }}" alt="{{ $data->first_name }}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;" class="mb-2">
                        @endif
                        <input type="file" name="images" class="form-control @error('images') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti foto.</small>
                        @error('images')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $data->first_name) }}">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $data->last_name) }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $data->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Handphone</label>
                            <input type="text" name="handphone" class="form-control @error('handphone') is-invalid @enderror" value="{{ old('handphone', $data->handphone) }}">
                            @error('handphone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Branch <span class="text-muted">(opsional)</span></label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">-- Belum ada Branch --</option>
                                @foreach ($companyBranches as $companyBranch)
                                    <option value="{{ $companyBranch->id }}" {{ old('branch_id', $data->branch_id) == $companyBranch->id ? 'selected' : '' }}>
                                        {{ $companyBranch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Form <span class="text-muted">(opsional)</span></label>
                            <select name="form_id" class="form-select @error('form_id') is-invalid @enderror">
                                <option value="">-- Belum ada Form --</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}" {{ old('form_id', $data->form_id) == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kode Sales</label>
                            <input type="text" name="sales_id" class="form-control @error('sales_id') is-invalid @enderror" value="{{ old('sales_id', $data->sales_id) }}" placeholder="Kode sales (opsional)">
                            @error('sales_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('student.student.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

@endsection
