@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('absensi.attendance-user-qr-code.index') }}">Attendance User QR Code</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('absensi.attendance-user-qr-code.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label class="mb-2">User</label>
                            <input type="text" class="form-control" value="{{ optional($data->user)->name ?? '-' }}" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="mb-2">Current Token</label>
                            <input type="text" class="form-control" value="{{ $data->qr_token }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="expires_at" class="mb-2">Expires At</label>
                            <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror"
                                id="expires_at" name="expires_at"
                                value="{{ old('expires_at', optional($data->expires_at)->format('Y-m-d\TH:i')) }}">
                            @error('expires_at')
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

                                <div class="col-xxl-12 mb-4">
                                    <label class="mb-2">QR PNG</label>
                                    @if (!empty($data->qr_code_path))
                                        <div>
                                            <a href="{{ asset('storage/' . $data->qr_code_path) }}" target="_blank">View PNG</a>
                                        </div>
                                    @else
                                        <div class="text-muted">-</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Update QR Code</button>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <button type="submit"
                                        formaction="{{ route('absensi.attendance-user-qr-code.generate-qr', $data->id) }}"
                                        formmethod="POST"
                                        class="btn btn-primary w-100"
                                        onclick="event.preventDefault(); document.getElementById('generate-qr-form').submit();">
                                        Generate QR
                                    </button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('absensi.attendance-user-qr-code.index') }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>

    <form id="generate-qr-form" action="{{ route('absensi.attendance-user-qr-code.generate-qr', $data->id) }}" method="POST" class="d-none">
        @csrf
    </form>

</div>

@endsection
