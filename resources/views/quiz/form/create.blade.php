@extends('layouts.frontend')
@section('content')
    <div class="middle-content container-xxl p-0">

        <div class="page-meta">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('quiz.form.index') }}">Quiz Form</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route('quiz.form.store') }}" method="POST">
            @csrf

            <div class="row mb-4 layout-spacing layout-top-spacing">

                <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="widget-content widget-content-area ecommerce-create-section">
                        <div class="row mb-4">
                            <div class="col-sm-12">

                                <label for="whatsapp_template_id" class="mb-2">
                                    Whatsapp Template
                                </label>


                                <select class="form-select @error('whatsapp_template_id') is-invalid @enderror"
                                    id="whatsapp_template_id" name="whatsapp_template_id">


                                    <option value="">
                                        -- Select Whatsapp Template --
                                    </option>


                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}"
                                            {{ old('whatsapp_template_id') == $template->id ? 'selected' : '' }}>

                                            {{ $template->name }}

                                        </option>
                                    @endforeach


                                </select>


                                @error('whatsapp_template_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="name" class="mb-2">Form Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" placeholder="Form Name" value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="description" class="mb-2">Description (Optional)</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="4" placeholder="Form Description">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="row">
                        <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4 mt-xxl-0">
                            <div class="widget-content widget-content-area ecommerce-create-section">
                                <div class="row">
                                    <div class="col-sm-12 mb-3">
                                        <button type="submit" class="btn btn-success w-100">Create Form</button>
                                    </div>
                                    <div class="col-sm-12">
                                        <a href="{{ route('quiz.form.index') }}"
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
