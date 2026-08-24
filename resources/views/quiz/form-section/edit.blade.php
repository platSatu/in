@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-section.update', $data->id) }}" method="POST" id="formSectionEditForm">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="form_id" class="mb-2">Form</label>
                            <select class="form-select @error('form_id') is-invalid @enderror" id="form_id" name="form_id">
                                <option value="">Choose form...</option>
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
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nama Section</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name', $data->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control @error('order') is-invalid @enderror"
                                name="order" value="{{ old('order', $data->order) }}" min="0">
                            @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active" {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="form-label">Description <span class="text-muted">(opsional)</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                name="description" rows="3">{{ old('description', $data->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm-3 mb-3">
                            <button type="submit" class="btn btn-success w-100">Update Section</button>
                        </div>
                        <div class="col-sm-3 mb-3">
                            <a href="{{ route('quiz.form-section.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

</div>

@endsection
