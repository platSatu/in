@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('roles.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="name" class="mb-2">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $data->name) }}" placeholder="Nama role">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="slug" class="mb-2">Slug (optional)</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" value="{{ old('slug', $data->slug) }}" placeholder="role-slug">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="status" class="mb-2">Status</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="scope_level" class="mb-2">Cakupan Data (Scope)</label>
                            <select id="scope_level" name="scope_level" class="form-select @error('scope_level') is-invalid @enderror">
                                <option value="company" {{ old('scope_level', $data->scope_level) === 'company' ? 'selected' : '' }}>Company — semua cabang &amp; divisi</option>
                                <option value="branch" {{ old('scope_level', $data->scope_level) === 'branch' ? 'selected' : '' }}>Branch — hanya 1 cabang (dipilih saat assign ke user)</option>
                                <option value="division" {{ old('scope_level', $data->scope_level) === 'division' ? 'selected' : '' }}>Division — hanya 1 divisi (dipilih saat assign ke user)</option>
                                <option value="self" {{ old('scope_level', $data->scope_level) === 'self' ? 'selected' : '' }}>Self — hanya data yang ditangani sendiri</option>
                            </select>
                            @error('scope_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Cabang/divisi spesifiknya ditentukan nanti di halaman "Role to User" saat role ini di-assign ke seorang user.
                            </small>
                        </div>
                    </div>

                    @include('roles._permissions-checklist')

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12 mt-4 mt-xxl-0">
                <div class="widget-content widget-content-area ecommerce-create-section">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-success w-100">Update Role</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
