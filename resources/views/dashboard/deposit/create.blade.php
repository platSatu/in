@extends('layouts.frontend')

@section('content')
    <div class="col-lg-6 mx-auto layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area">
                <h4 class="mb-1">Tambah Saldo</h4>
                <p class="text-muted">
                    Saldo saat ini: <strong>Rp {{ number_format((float) $currentBalance, 0, ',', '.') }}</strong>
                </p>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('dashboard.deposit.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="amount" class="form-label">Nominal Topup</label>
                        <input
                            type="number"
                            class="form-control @error('amount') is-invalid @enderror"
                            id="amount"
                            name="amount"
                            min="10000"
                            max="10000000"
                            step="1"
                            value="{{ old('amount') }}"
                            required
                        >
                        <small class="text-muted">Minimal Rp 10.000 dan maksimal Rp 10.000.000 per transaksi.</small>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <p class="text-muted small">
                        Kamu akan diarahkan ke halaman pembayaran gateway yang sedang aktif. Saldo
                        otomatis bertambah setelah pembayaran dikonfirmasi.
                    </p>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lanjut ke Pembayaran</button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
