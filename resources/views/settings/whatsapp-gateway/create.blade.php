@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('settings.whatsapp-gateway.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="gateway" class="mb-2">WhatsApp Gateway</label>
                            <select class="form-select @error('gateway') is-invalid @enderror" id="gateway" name="gateway">
                                @foreach ($gatewayOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('gateway', array_key_first($gatewayOptions)) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Baru ada 1 pilihan karena semua provider yang didukung sekarang pakai prosedur
                                pengiriman yang sama (API Host + Token + Secret Key).
                            </div>
                            @error('gateway')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="name" class="mb-2">Nama / Label <span class="text-muted">(opsional)</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Contoh: Wablas Utama" value="{{ old('name') }}">
                            <div class="form-text">Buat memudahkan kalau nanti simpan lebih dari 1 gateway.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="api_host" class="mb-2">API Host</label>
                            <input type="url" class="form-control @error('api_host') is-invalid @enderror"
                                id="api_host" name="api_host" placeholder="https://smg.wablas.com" value="{{ old('api_host') }}">
                            <div class="form-text">
                                Alamat dasar API provider (tanpa path), contoh: <code>https://smg.wablas.com</code>.
                                Sistem akan memanggil <code>{api_host}/api/v2/send-message</code> untuk kirim pesan.
                            </div>
                            @error('api_host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="token" class="mb-2">Token</label>
                            <input type="text" class="form-control @error('token') is-invalid @enderror"
                                id="token" name="token" placeholder="Token dari provider" value="{{ old('token') }}">
                            @error('token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="secret_key" class="mb-2">Secret Key</label>
                            <input type="text" class="form-control @error('secret_key') is-invalid @enderror"
                                id="secret_key" name="secret_key" placeholder="Secret key dari provider" value="{{ old('secret_key') }}">
                            <div class="form-text">Token dan Secret Key digabung sebagai header Authorization (token.secret_key).</div>
                            @error('secret_key')
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
                                            name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Jadikan gateway aktif
                                        </label>
                                    </div>
                                    <div class="form-text">Hanya boleh 1 gateway aktif dalam satu waktu.</div>
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
                                    <a href="{{ route('settings.whatsapp-gateway.index') }}"
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
