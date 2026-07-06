@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('quiz.form-answer.index') }}">Form Answer</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('quiz.form-answer.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="submission_id" class="mb-2">Submission</label>
                            <select class="form-select @error('submission_id') is-invalid @enderror" id="submission_id"
                                name="submission_id">
                                <option value="">Choose submission...</option>
                                @foreach ($submissions as $submission)
                                    <option value="{{ $submission->id }}"
                                        {{ old('submission_id', $data->submission_id) == $submission->id ? 'selected' : '' }}>
                                        {{ $submission->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('submission_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="question_id" class="mb-2">Question</label>
                            <select class="form-select @error('question_id') is-invalid @enderror" id="question_id"
                                name="question_id">
                                <option value="">Choose question...</option>
                                @foreach ($questions as $question)
                                    <option value="{{ $question->id }}"
                                        {{ old('question_id', $data->question_id) == $question->id ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 80) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('question_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="option_id" class="mb-2">Option (Optional)</label>
                            <select class="form-select @error('option_id') is-invalid @enderror" id="option_id"
                                name="option_id">
                                <option value="">Choose option...</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option->id }}"
                                        {{ old('option_id', $data->option_id) == $option->id ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($option->option_text, 80) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('option_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="answer_text" class="mb-2">Answer Text</label>
                            <textarea class="form-control @error('answer_text') is-invalid @enderror" id="answer_text" name="answer_text"
                                rows="4" placeholder="Enter answer text...">{{ old('answer_text', $data->answer_text) }}</textarea>
                            @error('answer_text')
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
                                    <button type="submit" class="btn btn-success w-100">Update Answer</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('quiz.form-answer.index') }}"
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
