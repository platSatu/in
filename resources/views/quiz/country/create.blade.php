@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Add Country</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('country.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label class="form-label">Country Name</label>

                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}">

                @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-control">{{ old('description') }}</textarea>

            </div>

            <button class="btn btn-primary">
                Save
            </button>

            <a href="{{ route('country.index') }}"
                class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
