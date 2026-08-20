@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Add Major</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('quiz.major.store') }}" method="POST">

            @csrf

            @php
                $lockedCityId = old('city_id', $selectedCityId ?? null);
                $lockedCity = $lockedCityId ? $cities->firstWhere('id', $lockedCityId) : null;
            @endphp

            <div class="mb-3">
                <label for="city_id" class="form-label">City</label>

                @if($lockedCity && !$errors->has('city_id'))
                    <input type="text" class="form-control" value="{{ $lockedCity->name }}" disabled readonly>
                    <input type="hidden" name="city_id" value="{{ $lockedCity->id }}">
                    <div class="form-text">
                        Major ini akan dikaitkan ke city di atas.
                        <a href="{{ route('quiz.major.create') }}">Ganti city</a>
                    </div>
                @else
                    <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id">
                        <option value="">Choose... (opsional)</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ $lockedCityId == $city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('city_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Major Name</label>

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

            <a href="{{ route('quiz.major.index') }}"
                class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
