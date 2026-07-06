@extends('layouts.frontend')

@section('content')
    <div class="col-lg-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area">
                <h4 class="mb-4">Checkout Package</h4>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive mb-4">
                    <table class="table">
                        <tr>
                            <th width="220">Package</th>
                            <td>{{ $package->name }}</td>
                        </tr>
                        <tr>
                            <th>Harga</th>
                            <td>Rp {{ number_format((float) $package->price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Durasi</th>
                            <td>{{ (int) $package->duration_days }} hari</td>
                        </tr>
                        <tr>
                            <th>Saldo Deposit Anda</th>
                            <td>Rp {{ number_format((float) $currentBalance, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>

                @if (!$canPay)
                    <div class="alert alert-warning">
                        Saldo deposit Anda tidak cukup untuk membeli package ini.
                    </div>
                @endif

                <div class="d-flex gap-2">
                    <a href="{{ route('public.packages.index') }}" class="btn btn-outline-secondary">Kembali</a>

                    <form action="{{ route('public.packages.pay', $package->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary" {{ !$canPay ? 'disabled' : '' }}>
                            Bayar dengan Deposit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
