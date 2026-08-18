@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('quiz.form-question.index') }}">Form Question</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('quiz.form-question.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="form_id" class="mb-2">Form</label>
                            <select class="form-select @error('form_id') is-invalid @enderror" id="form_id" name="form_id">
                                <option value="">Choose form...</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}"
                                        {{ old('form_id', $data->form_id) == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="question_text" class="mb-2">Question Text</label>
                            <textarea class="form-control @error('question_text') is-invalid @enderror" id="question_text" name="question_text"
                                rows="4" placeholder="Enter question text...">{{ old('question_text', $data->question_text) }}</textarea>
                            @error('question_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-7 mt-xxl-0 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-xxl-12 mb-4">
                                    <label for="type">Type</label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                                        <option value="">Choose...</option>
                                        <option value="text"
                                            {{ old('type', $data->type) === 'text' ? 'selected' : '' }}>Text (single line)</option>
                                        <option value="textarea"
                                            {{ old('type', $data->type) === 'textarea' ? 'selected' : '' }}>Textarea (long text)</option>
                                        <option value="number"
                                            {{ old('type', $data->type) === 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="date"
                                            {{ old('type', $data->type) === 'date' ? 'selected' : '' }}>Date</option>
                                        <option value="single_choice"
                                            {{ old('type', $data->type) === 'single_choice' ? 'selected' : '' }}>Single Choice (radio / checkbox-style)</option>
                                        <option value="multiple_choice"
                                            {{ old('type', $data->type) === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice (checkbox)</option>
                                        <option value="dropdown"
                                            {{ old('type', $data->type) === 'dropdown' ? 'selected' : '' }}>Dropdown (select, ringkas untuk opsi banyak)</option>
                                        <option value="major"
                                            {{ old('type', $data->type) === 'major' ? 'selected' : '' }}>Major</option>
                                    </select>
                                    <div class="form-text">
                                        Single Choice, Multiple Choice, dan Dropdown ambil pilihan jawabannya dari menu "Options" di pertanyaan ini.
                                    </div>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="required">Required</label>
                                    <select class="form-select @error('required') is-invalid @enderror" id="required" name="required">
                                        <option value="1" {{ old('required', (string) (int) $data->required) === '1' ? 'selected' : '' }}>Yes, wajib diisi</option>
                                        <option value="0" {{ old('required', (string) (int) $data->required) === '0' ? 'selected' : '' }}>No, boleh kosong</option>
                                    </select>
                                    @error('required')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="order">Order</label>
                                    <input type="number" min="0" class="form-control @error('order') is-invalid @enderror"
                                        id="order" name="order" value="{{ old('order', $data->order) }}">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="">Choose...</option>
                                        <option value="active"
                                            {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive"
                                            {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Update Question</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('quiz.form-question.index') }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection