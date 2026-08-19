@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company.division.index') }}">Company Division</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <div class="widget-content widget-content-area layout-top-spacing">

        <form action="{{ route('company.division.store') }}" method="POST">
            @csrf

            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="company_branch_id" class="form-label">Company Branch</label>

                    @php
                        $lockedCompanyBranchId = old('company_branch_id', $selectedCompanyBranchId ?? null);
                        $lockedCompanyBranch = $lockedCompanyBranchId ? $companyBranches->firstWhere('id', $lockedCompanyBranchId) : null;
                    @endphp

                    @if ($lockedCompanyBranch && !$errors->has('company_branch_id'))
                        <input type="text" class="form-control" value="{{ $lockedCompanyBranch->name }}" disabled readonly>
                        <input type="hidden" name="company_branch_id" value="{{ $lockedCompanyBranch->id }}">
                        <div class="form-text">
                            Divisi ini akan dikaitkan ke company branch di atas.
                            <a href="{{ route('company.division.create') }}">Ganti company branch</a>
                        </div>
                    @else
                        <select class="form-select @error('company_branch_id') is-invalid @enderror"
                            id="company_branch_id" name="company_branch_id">
                            <option value="">Choose company branch...</option>
                            @foreach ($companyBranches as $companyBranch)
                                <option value="{{ $companyBranch->id }}"
                                    {{ $lockedCompanyBranchId == $companyBranch->id ? 'selected' : '' }}>
                                    {{ $companyBranch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                        name="name" value="{{ old('name') }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-8">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                        name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
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
            <a href="{{ route('company.division.index') }}" class="btn btn-outline-secondary">Cancel</a>

        </form>

    </div>

</div>

@endsection
