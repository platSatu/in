@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">


    <div class="widget-content widget-content-area">

        <form action="{{ route('course.class.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>

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
                <label class="form-label">Description</label>

                <textarea
                    name="description"
                    rows="4"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>

                @error('description')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Fee Pengajar (Rp)</label>

                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input
                        type="number"
                        name="teacher_fee"
                        min="0"
                        step="1"
                        class="form-control @error('teacher_fee') is-invalid @enderror"
                        value="{{ old('teacher_fee', 0) }}"
                        placeholder="20000">
                </div>

                <div class="form-text">Honor yang diterima pengajar untuk setiap kelas jenis ini dalam satu periode, berapa pun jumlah pertemuan dan siswanya.</div>

                @error('teacher_fee')
                    <div class="text-danger small">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>

                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                </select>

                @error('status')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button class="btn btn-primary">
                Save
            </button>

            <a href="{{ route('course.class.index') }}" class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection
