@extends('layouts.frontend')

@section('content')
    <div class="col-lg-6 mx-auto layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area">
                <h4 class="mb-1">Pilih Metode Pembayaran</h4>
                <p class="text-muted">
                    Topup sebesar <strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                </p>

                @if (session('error') || isset($error))
                    <div class="alert alert-danger">{{ session('error') ?? $error }}</div>
                @endif

                @if (empty($methods))
                    <p class="text-muted">Tidak ada metode pembayaran yang bisa ditampilkan saat ini.</p>
                    <a href="{{ route('dashboard.deposit.create') }}" class="btn btn-outline-secondary">Kembali</a>
                @else
                    <form method="POST" action="{{ route('dashboard.deposit.select-method.submit', ['order_id' => $payment->order_id]) }}">
                        @csrf

                        <div class="list-group mb-3">
                            @foreach ($methods as $method)
                                <label class="list-group-item d-flex align-items-center gap-2">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $method['code'] }}"
                                        class="form-check-input mt-0"
                                        required
                                    >

                                    @if (!empty($method['image']))
                                        <img src="{{ $method['image'] }}" alt="{{ $method['name'] }}" height="24">
                                    @endif

                                    <span class="flex-grow-1">{{ $method['name'] }}</span>

                                    @if (!empty($method['fee']))
                                        <span class="text-muted small">Fee: {{ $method['fee'] }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Bayar</button>
                            <a href="{{ route('dashboard.deposit.create') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
