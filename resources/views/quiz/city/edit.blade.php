@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit City</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('city.update',$data->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="country_id" class="form-label">Country</label>

                <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id">
                    <option value="">Choose... (opsional)</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" {{ old('country_id', $data->country_id) == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>

                @error('country_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">

                <label class="form-label">
                    City Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name',$data->name) }}">

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
                    class="form-control">{{ old('description',$data->description) }}</textarea>

            </div>

            <button class="btn btn-primary">
                Update
            </button>

            <a href="{{ route('city.index') }}"
                class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
