@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('absensi.attendance.index') }}">Attendance</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('absensi.attendance.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="attendance_date" class="mb-2">Attendance Date</label>
                            <input type="date" class="form-control @error('attendance_date') is-invalid @enderror"
                                id="attendance_date" name="attendance_date" value="{{ old('attendance_date') }}">
                            @error('attendance_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="attendance_setting_id" class="mb-2">Attendance Setting</label>
                            <select class="form-select @error('attendance_setting_id') is-invalid @enderror"
                                id="attendance_setting_id" name="attendance_setting_id">
                                <option value="">Choose...</option>
                                @foreach ($settings as $setting)
                                    <option value="{{ $setting->id }}"
                                        {{ old('attendance_setting_id') === $setting->id ? 'selected' : '' }}>
                                        {{ $setting->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('attendance_setting_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="check_in_time" class="mb-2">Check In Time</label>
                            <input type="time" class="form-control @error('check_in_time') is-invalid @enderror"
                                id="check_in_time" name="check_in_time" value="{{ old('check_in_time') }}">
                            @error('check_in_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="check_out_time" class="mb-2">Check Out Time</label>
                            <input type="time" class="form-control @error('check_out_time') is-invalid @enderror"
                                id="check_out_time" name="check_out_time" value="{{ old('check_out_time') }}">
                            @error('check_out_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="late_minutes" class="mb-2">Late Minutes</label>
                            <input type="number" min="0"
                                class="form-control @error('late_minutes') is-invalid @enderror" id="late_minutes"
                                name="late_minutes" value="{{ old('late_minutes') }}">
                            @error('late_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="work_hours" class="mb-2">Work Hours</label>
                            <input type="number" step="0.01" min="0"
                                class="form-control @error('work_hours') is-invalid @enderror" id="work_hours"
                                name="work_hours" value="{{ old('work_hours') }}">
                            @error('work_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-3">
                            <label for="check_in_lat" class="mb-2">Check In Lat</label>
                            <input type="text" class="form-control @error('check_in_lat') is-invalid @enderror"
                                id="check_in_lat" name="check_in_lat" value="{{ old('check_in_lat') }}">
                            @error('check_in_lat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-3">
                            <label for="check_in_lng" class="mb-2">Check In Lng</label>
                            <input type="text" class="form-control @error('check_in_lng') is-invalid @enderror"
                                id="check_in_lng" name="check_in_lng" value="{{ old('check_in_lng') }}">
                            @error('check_in_lng')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-3">
                            <label for="check_out_lat" class="mb-2">Check Out Lat</label>
                            <input type="text" class="form-control @error('check_out_lat') is-invalid @enderror"
                                id="check_out_lat" name="check_out_lat" value="{{ old('check_out_lat') }}">
                            @error('check_out_lat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-3">
                            <label for="check_out_lng" class="mb-2">Check Out Lng</label>
                            <input type="text" class="form-control @error('check_out_lng') is-invalid @enderror"
                                id="check_out_lng" name="check_out_lng" value="{{ old('check_out_lng') }}">
                            @error('check_out_lng')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="check_in_method" class="mb-2">Check In Method</label>
                            <input type="text" class="form-control @error('check_in_method') is-invalid @enderror"
                                id="check_in_method" name="check_in_method" value="{{ old('check_in_method') }}">
                            @error('check_in_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="device_info" class="mb-2">Device Info</label>
                            <input type="text" class="form-control @error('device_info') is-invalid @enderror"
                                id="device_info" name="device_info" value="{{ old('device_info') }}">
                            @error('device_info')
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
                                        <option value="present" {{ old('status') === 'present' ? 'selected' : '' }}>Present</option>
                                        <option value="late" {{ old('status') === 'late' ? 'selected' : '' }}>Late</option>
                                        <option value="absent" {{ old('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="leave" {{ old('status') === 'leave' ? 'selected' : '' }}>Leave</option>
                                        <option value="sick" {{ old('status') === 'sick' ? 'selected' : '' }}>Sick</option>
                                        <option value="permission" {{ old('status') === 'permission' ? 'selected' : '' }}>Permission</option>
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
                                    <button type="submit" class="btn btn-success w-100">Create Attendance</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('absensi.attendance.index') }}"
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
