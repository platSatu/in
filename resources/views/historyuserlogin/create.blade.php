@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('historyuserlogin.index') }}">History User Login</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('historyuserlogin.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">

                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="last_login" class="mb-2">Last Login</label>
                            <input type="datetime-local"
                                   class="form-control @error('last_login') is-invalid @enderror"
                                   id="last_login" name="last_login"
                                   value="{{ old('last_login') }}">
                            @error('last_login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="last_logout" class="mb-2">Last Logout</label>
                            <input type="datetime-local"
                                   class="form-control @error('last_logout') is-invalid @enderror"
                                   id="last_logout" name="last_logout"
                                   value="{{ old('last_logout') }}">
                            @error('last_logout')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="duration" class="mb-2">Duration</label>
                            <input type="text"
                                   class="form-control @error('duration') is-invalid @enderror"
                                   id="duration" name="duration"
                                   value="{{ old('duration') }}" placeholder="Contoh: 2 jam 15 menit">
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12 mt-4 mt-xxl-0">
                <div class="widget-content widget-content-area ecommerce-create-section">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-success w-100">Create History Login</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('historyuserlogin.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
