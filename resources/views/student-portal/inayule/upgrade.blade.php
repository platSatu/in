@extends('layouts.frontend')
@section('content')

{{--
    Upgrade/Konversi Paket (FASE 4 bagian 2, 16 September 2026) -- lihat
    docblock App\Services\CoursePackagePayment\PackageUpgradeCalculator
    untuk urutan lengkap penutupan harga: trade-in saldo credit lama dulu,
    baru saldo Deposit, baru payment gateway. Halaman ini MURNI tampilan
    (GET, tidak mengubah data apa pun) -- semua angka DIHITUNG ULANG lagi
    di server saat submit (store()), jadi kalaupun saldo/credit berubah
    tepat setelah halaman ini dibuka, angka yang benar-benar diproses
    tetap yang terbaru.
--}}

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Upgrade Package</h4>
            <p class="text-muted mb-4">{{ $package->name }}</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <div class="d-flex justify-content-between py-1">
                    <span>Harga Package Baru</span>
                    <span class="fw-bold">Rp {{ number_format($preview['target_price'], 0, ',', '.') }}</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between py-1 text-muted">
                    <span>Sisa Credit Anda Sekarang</span>
                    <span>{{ number_format($preview['credit_balance'], 2, ',', '.') }} credit</span>
                </div>
                <div class="d-flex justify-content-between py-1 text-muted">
                    <span>Nilai Tukar (Trade-in) Credit Lama</span>
                    <span>Rp {{ number_format($preview['credit_trade_in_value'], 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Dipakai Menutup Harga Package Baru</span>
                    <span>Rp {{ number_format($preview['trade_in_applied_to_price'], 0, ',', '.') }}</span>
                </div>

                @if ($preview['trade_in_leftover_to_deposit'] > 0)
                    <div class="d-flex justify-content-between py-1 text-success">
                        <span>Sisa Trade-in Masuk Saldo Anda</span>
                        <span>Rp {{ number_format($preview['trade_in_leftover_to_deposit'], 0, ',', '.') }}</span>
                    </div>
                @endif

                <hr>

                <div class="d-flex justify-content-between py-1 text-muted">
                    <span>Sisa Harga Setelah Trade-in</span>
                    <span>Rp {{ number_format($preview['remaining_price_after_trade_in'], 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Dibayar pakai Saldo</span>
                    <span>Rp {{ number_format($preview['deposit_portion'], 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Sisa via Payment Gateway</span>
                    <span class="fw-bold">Rp {{ number_format($preview['gateway_portion'], 0, ',', '.') }}</span>
                </div>
            </div>

            @if ($gatewayMissing)
                <div class="alert alert-warning">
                    Payment gateway belum diaktifkan oleh admin, jadi sisa tagihan di atas tidak bisa diproses. Silakan hubungi admin.
                </div>
            @else
                <p class="text-muted small">
                    @if ($preview['gateway_portion'] <= 0)
                        Upgrade ini 100% tertutup trade-in credit lama + saldo Anda -- diproses instan tanpa perlu ke halaman pembayaran lain.
                    @else
                        Trade-in credit lama & saldo Anda otomatis dipakai dulu, sisanya akan diarahkan ke halaman pembayaran gateway.
                    @endif
                </p>
            @endif

            <div class="alert alert-info small">
                Sisa credit lama Anda akan ditukar (trade-in) sepenuhnya untuk upgrade ini -- tidak dicairkan tunai, tetap tersimpan sebagai nilai rupiah yang dipakai menutup harga package baru (kelebihannya masuk saldo Anda untuk pembelian berikutnya).
            </div>

            <form method="POST" action="{{ route('inayule.upgrade.store', $package->id) }}">
                @csrf
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" @disabled($gatewayMissing)>Upgrade Sekarang</button>
                    <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
