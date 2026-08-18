@extends('layouts.frontend')
@section('content')
<div class="middle-content container-xxl p-0">
    <div class="page-meta mb-3">
        <h4>Add University Album</h4>
    </div>
    <div class="widget-content widget-content-area">
        <form action="{{ route('quiz.university-album.store') }}" method="POST">
            @csrf

            @php
                $lockedUniversityId = old('university_id', $selectedUniversityId ?? null);
                $lockedUniversity = $lockedUniversityId ? $universities->firstWhere('id', $lockedUniversityId) : null;
            @endphp

            <div class="mb-3">
                <label for="university_id" class="form-label"> University</label>

                @if($lockedUniversity && !$errors->has('university_id'))
                    <input type="text" class="form-control" value="{{ $lockedUniversity->name }}" disabled readonly>
                    <input type="hidden" name="university_id" value="{{ $lockedUniversity->id }}">
                    <div class="form-text">
                        Album ini akan dikaitkan ke university di atas.
                        <a href="{{ route('quiz.university-album.create') }}">Ganti university</a>
                    </div>
                @else
                    <select
                        class="form-select @error('university_id') is-invalid @enderror"
                        id="university_id"
                        name="university_id">
                        <option value=""> Choose...</option>
                        @foreach($universities as $university)
                            <option value="{{ $university->id }}"
                                {{ $lockedUniversityId == $university->id ? 'selected' : '' }}>
                                {{ $university->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('university_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                @endif
            </div>
            <div class="mb-3">
                <label class="form-label">Album Name</label>
                <input
                    type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea
                    name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}
                    </div>
                @enderror
            </div>
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('quiz.university-album.index') }}" class="btn btn-secondary"> Back</a>
        </form>
    </div>
</div>
@endsection