@extends('layouts.frontend')
@section('content')

{{--
    Checkout package berbayar (STEP 6, 16 September 2026) -- lihat docblock
    InaYulePackageCheckoutController untuk alur lengkap "mix otomatis" saldo
    Deposit + payment gateway. Halaman ini MURNI tampilan (GET, tidak
    mengubah data apa pun) -- split deposit_portion/gateway_portion yang
    ditampilkan di sini DIHITUNG ULANG lagi di server saat submit (store()),
    jadi kalaupun saldo berubah tepat setelah halaman ini dibuka, angka yang
    benar-benar diproses tetap yang terbaru.
--}}

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Checkout Package</h4>
            <p class="text-muted mb-4">{{ $package->name }}</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <div class="d-flex justify-content-between py-1">
                    <span>Harga Package</span>
                    <span class="fw-bold">Rp {{ number_format($price, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 text-muted">
                    <span>Sisa Saldo Anda</span>
                    <span>Rp {{ number_format($depositBalance, 0, ',', '.') }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between py-1">
                    <span>Dibayar pakai Saldo</span>
                    <span>Rp {{ number_format($depositPortion, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Sisa via Payment Gateway</span>
                    <span class="fw-bold">Rp {{ number_format($gatewayPortion, 0, ',', '.') }}</span>
                </div>
            </div>

            @if ($gatewayMissing)
                <div class="alert alert-warning">
                    Payment gateway belum diaktifkan oleh admin, jadi sisa tagihan di atas saldo Anda belum bisa diproses. Silakan hubungi admin.
                </div>
            @else
                <p class="text-muted small">
                    @if ($gatewayPortion <= 0)
                        Pembelian ini 100% tertutup saldo Anda -- diproses instan tanpa perlu ke halaman pembayaran lain.
                    @else
                        Saldo Anda otomatis dipakai dulu, sisanya akan diarahkan ke halaman pembayaran gateway.
                    @endif
                </p>
            @endif

            <form method="POST" action="{{ route('inayule.checkout.store', $package->id) }}">
                @csrf
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" @disabled($gatewayMissing)>Bayar Sekarang</button>
                    <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
