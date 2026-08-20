@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Add City</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('city.store') }}" method="POST">

            @csrf

            @php
                $lockedCountryId = old('country_id', $selectedCountryId ?? null);
                $lockedCountry = $lockedCountryId ? $countries->firstWhere('id', $lockedCountryId) : null;
            @endphp

            <div class="mb-3">
                <label for="country_id" class="form-label">Country</label>

                @if($lockedCountry && !$errors->has('country_id'))
                    <input type="text" class="form-control" value="{{ $lockedCountry->name }}" disabled readonly>
                    <input type="hidden" name="country_id" value="{{ $lockedCountry->id }}">
                    <div class="form-text">
                        City ini akan dikaitkan ke country di atas.
                        <a href="{{ route('city.create') }}">Ganti country</a>
                    </div>
                @else
                    <select class="form-select @error('country_id') is-invalid @enderror" id="country_id" name="country_id">
                        <option value="">Choose... (opsional)</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ $lockedCountryId == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">City Name</label>

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

            <a href="{{ route('city.index') }}"
                class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
