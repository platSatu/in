@extends('layouts.frontend')
@section('content')

<!-- Kolom Utama -->
<div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
    <div class="widget-content widget-content-area ecommerce-create-section">
        <form method="POST" action="{{ route('profile-bussines.store') }}">
            @csrf

            <!-- Name -->
            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="name" class="form-label">Business Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}" 
                           placeholder="Enter business name" 
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="description">Description</label>
                    <div id="business-description" class="form-control @error('description') is-invalid @enderror" 
                         style="min-height: 150px;">{!! old('description') !!}</div>
                    <input type="hidden" name="description" id="description" value="{{ old('description') }}">
                    @error('description')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Parent -->
            <div class="row mb-4">
                <div class="col-sm-12">
                    <label for="parent_id">Parent Business</label>
                    <select class="form-select @error('parent_id') is-invalid @enderror" 
                            id="parent_id" 
                            name="parent_id">
                        <option value="">-- Select Parent (Optional) --</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-success w-100">Save Business</button>
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Sidebar Info -->
<div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
    <div class="row">
        <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12">
            <div class="widget-content widget-content-area ecommerce-create-section">
                <div class="row">
                    <!-- Status -->
                    <div class="col-sm-12 mb-4">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <div class="switch form-switch-custom switch-inline form-switch-secondary">
                            <input class="switch-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   id="status" 
                                   name="status"
                                   value="active"
                                   {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                            <label class="switch-label" for="status">
                                {{ old('status', 'active') == 'active' ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tombol Kembali -->
                    <div class="col-sm-12">
                        <a href="{{ route('profile-bussines.index') }}" class="btn btn-secondary w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection