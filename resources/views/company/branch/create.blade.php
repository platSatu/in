@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company.branch.index') }}">Company Branch</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <div class="widget-content widget-content-area layout-top-spacing">

        <form action="{{ route('company.branch.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="company_profile_id" class="form-label">Company Profile</label>

                    @php
                        $lockedCompanyProfileId = old('company_profile_id', $selectedCompanyProfileId ?? null);
                        $lockedCompanyProfile = $lockedCompanyProfileId ? $companyProfiles->firstWhere('id', $lockedCompanyProfileId) : null;
                    @endphp

                    @if ($lockedCompanyProfile && !$errors->has('company_profile_id'))
                        <input type="text" class="form-control" value="{{ $lockedCompanyProfile->name }}" disabled readonly>
                        <input type="hidden" name="company_profile_id" value="{{ $lockedCompanyProfile->id }}">
                        <div class="form-text">
                            Branch ini akan dikaitkan ke company profile di atas.
                            <a href="{{ route('company.branch.create') }}">Ganti company profile</a>
                        </div>
                    @else
                        <select class="form-select @error('company_profile_id') is-invalid @enderror"
                            id="company_profile_id" name="company_profile_id">
                            <option value="">Choose company profile...</option>
                            @foreach ($companyProfiles as $companyProfile)
                                <option value="{{ $companyProfile->id }}"
                                    {{ $lockedCompanyProfileId == $companyProfile->id ? 'selected' : '' }}>
                                    {{ $companyProfile->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_profile_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                        name="name" value="{{ old('name') }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-6">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo"
                        name="logo" accept="image/*">
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                        name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address"
                        name="address" rows="2">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-4">
                    <label for="handphone" class="form-label">Handphone</label>
                    <input type="text" class="form-control @error('handphone') is-invalid @enderror" id="handphone"
                        name="handphone" value="{{ old('handphone') }}">
                    @error('handphone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                        name="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('company.branch.index') }}" class="btn btn-outline-secondary">Cancel</a>

        </form>

    </div>

</div>

@endsection
