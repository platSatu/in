@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit Class Schedule</h4>
    </div>

    <div class="widget-content widget-content-area">

        <form action="{{ route('quiz.class-schedule.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $data->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nama Kelas</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    placeholder="Contoh: Mandarin Basic 1" value="{{ old('name', $data->name) }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="level" class="form-label">Level (Optional)</label>
                <input type="text" class="form-control @error('level') is-invalid @enderror" id="level" name="level"
                    placeholder="Contoh: HSK 1" value="{{ old('level', $data->level) }}">
                @error('level')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-sm-6">
                    <label for="class_date" class="form-label">Tanggal</label>
                    <input type="date" class="form-control @error('class_date') is-invalid @enderror"
                        id="class_date" name="class_date"
                        value="{{ old('class_date', optional($data->class_date)->format('Y-m-d')) }}">
                    @error('class_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-6">
                    <label for="start_time" class="form-label">Jam (Optional)</label>
                    <input type="time" class="form-control @error('start_time') is-invalid @enderror"
                        id="start_time" name="start_time"
                        value="{{ old('start_time', $data->start_time ? substr($data->start_time, 0, 5) : '') }}">
                    @error('start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Kuota / Kapasitas Maksimal</label>
                <input type="number" min="1" class="form-control @error('capacity') is-invalid @enderror"
                    id="capacity" name="capacity" value="{{ old('capacity', $data->capacity) }}">
                <div class="form-text">Sisa slot dihitung otomatis dari jumlah peserta yang sudah terdaftar.</div>
                @error('capacity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ old('status', $data->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $data->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <button class="btn btn-primary">Save</button>
            <a href="{{ route('quiz.class-schedule.index') }}" class="btn btn-secondary">Back</a>

        </form>

    </div>

</div>

@endsection
