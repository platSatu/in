@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit Course Package</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('course.package.update', $data->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Package Name</label>

                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $data->name) }}">

                @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Course Type
                        <a href="{{ route('course.type.create') }}" class="ms-1 small">+ Add new</a>
                    </label>

                    <select name="course_type_id" class="form-select @error('course_type_id') is-invalid @enderror">
                        <option value="">-- Select Course Type --</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('course_type_id', $data->course_type_id) == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>

                    @error('course_type_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Course Class
                        <a href="{{ route('course.class.create') }}" class="ms-1 small">+ Add new</a>
                    </label>

                    <select name="course_class_id" class="form-select @error('course_class_id') is-invalid @enderror">
                        <option value="">-- Select Course Class --</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('course_class_id', $data->course_class_id) == $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>

                    @error('course_class_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Course Level
                        <a href="{{ route('course.level.create') }}" class="ms-1 small">+ Add new</a>
                    </label>

                    <select name="course_level_id" class="form-select @error('course_level_id') is-invalid @enderror">
                        <option value="">-- Select Course Level --</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('course_level_id', $data->course_level_id) == $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </select>

                    @error('course_level_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Duration</label>

                    <input
                        type="number"
                        name="duration_value"
                        min="1"
                        max="120"
                        class="form-control @error('duration_value') is-invalid @enderror"
                        value="{{ old('duration_value', $data->duration_value) }}">

                    @error('duration_value')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Duration Unit</label>

                    <select name="duration_unit" class="form-select @error('duration_unit') is-invalid @enderror">
                        @foreach ($durationUnits as $unit)
                            <option value="{{ $unit }}" @selected(old('duration_unit', $data->duration_unit) === $unit)>{{ ucfirst($unit) }}</option>
                        @endforeach
                    </select>

                    @error('duration_unit')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Price (Rp)</label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        class="form-control @error('price') is-invalid @enderror"
                        value="{{ old('price', $data->price) }}">

                    @error('price')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Credits</label>

                    <input
                        type="number"
                        name="credits"
                        min="0.01"
                        step="0.01"
                        class="form-control @error('credits') is-invalid @enderror"
                        value="{{ old('credits', $data->credits) }}">

                    @error('credits')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>

                <textarea
                    name="description"
                    rows="3"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $data->description) }}</textarea>

                @error('description')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>

                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $data->status) === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $data->status) === 'inactive')>Inactive</option>
                </select>

                @error('status')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button class="btn btn-primary">
                Update
            </button>

            <a href="{{ route('course.package.index') }}" class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
