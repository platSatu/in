@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('settings.zoom.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="name" class="mb-2">Nama / Label <span class="text-muted">(opsional)</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Contoh: Zoom Account Utama" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-info">
                        Kredensial di bawah didapat dari app <strong>Server-to-Server OAuth</strong> yang dibuat di
                        <a href="https://marketplace.zoom.us" target="_blank" rel="noopener">marketplace.zoom.us</a>
                        (halaman App Credentials).
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="account_id" class="mb-2">Account ID</label>
                            <input type="text" class="form-control @error('account_id') is-invalid @enderror"
                                id="account_id" name="account_id" value="{{ old('account_id') }}" autocomplete="off">
                            @error('account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="client_id" class="mb-2">Client ID</label>
                            <input type="text" class="form-control @error('client_id') is-invalid @enderror"
                                id="client_id" name="client_id" value="{{ old('client_id') }}" autocomplete="off">
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="client_secret" class="mb-2">Client Secret</label>
                            <input type="password" class="form-control @error('client_secret') is-invalid @enderror"
                                id="client_secret" name="client_secret" value="{{ old('client_secret') }}" autocomplete="new-password">
                            @error('client_secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="secret_token" class="mb-2">Secret Token <span class="text-muted">(opsional)</span></label>
                            <input type="password" class="form-control @error('secret_token') is-invalid @enderror"
                                id="secret_token" name="secret_token" value="{{ old('secret_token') }}" autocomplete="new-password">
                            <div class="form-text">Dipakai untuk verifikasi webhook Zoom (belum aktif dipakai saat ini, boleh dikosongkan).</div>
                            @error('secret_token')
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
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                            name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Jadikan konfigurasi aktif
                                        </label>
                                    </div>
                                    <div class="form-text">Hanya boleh 1 konfigurasi Zoom aktif dalam satu waktu.</div>
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="owner_user_id">Pemilik (Admin)</label>
                                    <select class="form-select @error('owner_user_id') is-invalid @enderror" id="owner_user_id" name="owner_user_id">
                                        @foreach ($adminUsers as $adminUser)
                                            <option value="{{ $adminUser->id }}" {{ old('owner_user_id', (string) auth()->id()) === (string) $adminUser->id ? 'selected' : '' }}>
                                                {{ $adminUser->name }} ({{ $adminUser->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('owner_user_id')
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
                                    <button type="submit" class="btn btn-success w-100">Save</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('settings.zoom.index') }}"
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
