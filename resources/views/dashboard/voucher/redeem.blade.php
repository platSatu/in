@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Redeem Voucher</li>
            </ol>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-6 col-lg-8 col-md-10 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <form action="{{ route('dashboard.voucher.redeem.submit') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="code_voucher" class="form-label">Kode Voucher</label>
                        <input
                            type="text"
                            id="code_voucher"
                            name="code_voucher"
                            class="form-control @error('code_voucher') is-invalid @enderror"
                            placeholder="Masukkan kode voucher"
                            value="{{ old('code_voucher') }}"
                        >
                        @error('code_voucher')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid d-sm-flex gap-2">
                        <button type="submit" class="btn btn-primary">Redeem</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
