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

<div class="col-lg-6 mx-auto layout-spacing inayule-sub">
    @include('student-portal.inayule._subpage', ['backTab' => 'beli'])
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Checkout Package</h4>
            <p class="text-muted mb-4">{{ $package->name }}</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- Voucher Paket (20 November 2026): pratinjau harga lewat GET, kuota baru dipakai saat Bayar Sekarang. --}}
            <form method="GET" action="{{ route('inayule.checkout.show', $package->id) }}" class="mb-3">
                <label class="form-label small text-muted mb-1">Punya kode voucher?</label>
                @if ($referralCode !== '')
                    <input type="hidden" name="referral" value="{{ $referralCode }}">
                @endif
                <div class="input-group">
                    <input type="text" name="voucher" maxlength="50" class="form-control text-uppercase" placeholder="Masukkan kode voucher"
                        value="{{ $voucherCode }}" autocomplete="off">
                    <button class="btn btn-outline-primary">Pakai</button>
                    @if ($voucherCode !== '')
                        <a href="{{ route('inayule.checkout.show', ['packageId' => $package->id, 'referral' => $referralCode ?: null]) }}" class="btn btn-outline-secondary" title="Hapus voucher">&times;</a>
                    @endif
                </div>
                @if ($voucherResult)
                    <div @class(['small mt-1', 'text-success' => $voucherResult['ok'], 'text-danger' => ! $voucherResult['ok']])>{{ $voucherResult['message'] }}</div>
                @endif
            </form>

            {{-- Kode Referal (25 November 2026): hanya untuk pembelian pertama murid baru. Pratinjau lewat GET;
                 tidak mengubah harga, hanya mencatat siapa yang mengajak (dicek ulang di server saat Bayar Sekarang). --}}
            @if ($referralAvailable)
                <form method="GET" action="{{ route('inayule.checkout.show', $package->id) }}" class="mb-3">
                    @if ($voucherCode !== '')
                        <input type="hidden" name="voucher" value="{{ $voucherCode }}">
                    @endif
                    <label class="form-label small text-muted mb-1">Diajak teman? Masukkan kode referalnya</label>
                    <div class="input-group">
                        <input type="text" name="referral" maxlength="20" class="form-control text-uppercase" placeholder="mis. BUDI-K7Q2M"
                            value="{{ $referralCode }}" autocomplete="off">
                        <button class="btn btn-outline-primary">Pakai</button>
                        @if ($referralCode !== '')
                            <a href="{{ route('inayule.checkout.show', ['packageId' => $package->id, 'voucher' => $voucherCode ?: null]) }}" class="btn btn-outline-secondary" title="Hapus kode referal">&times;</a>
                        @endif
                    </div>
                    @if ($referralResult)
                        <div @class(['small mt-1', 'text-success' => $referralResult['ok'], 'text-danger' => ! $referralResult['ok']])>{{ $referralResult['message'] }}</div>
                    @else
                        <div class="small text-muted mt-1">Khusus pembelian pertama. Teman yang mengajak Anda akan mendapat bonus.</div>
                    @endif
                </form>
            @elseif ($referralProgramActive)
                {{-- Murid lama: kolom kode referal tidak tampil, beri tahu alasannya supaya tidak bingung. --}}
                <p class="small text-muted mb-3">Kode referal hanya bisa dipakai di pembelian pertama murid baru INA YULE.</p>
            @endif

            <div class="mb-4">
                @if ($voucherResult && $voucherResult['ok'])
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Harga Package</span>
                        <span class="text-decoration-line-through">Rp {{ number_format($originalPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 text-success">
                        <span>Diskon Voucher {{ $voucherResult['voucher']->code }}</span>
                        <span>- Rp {{ number_format($voucherResult['discount'], 0, ',', '.') }}</span>
                    </div>
                @endif
                @if ($referralResult && $referralResult['ok'])
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Kode Referal</span>
                        <span>{{ $referralResult['referrer']->referral_code }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between py-1">
                    <span>{{ $voucherResult && $voucherResult['ok'] ? 'Harga Setelah Diskon' : 'Harga Package' }}</span>
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

            <form method="POST" action="{{ route('inayule.checkout.store', $package->id) }}" onsubmit="this.querySelector('[type=submit]').disabled = true;">
                @csrf
                @if ($voucherResult && $voucherResult['ok'])
                    <input type="hidden" name="voucher_code" value="{{ $voucherResult['voucher']->code }}">
                @endif
                {{-- Hanya kodenya yang dikirim; syaratnya dicek ulang di server. --}}
                @if ($referralResult && $referralResult['ok'])
                    <input type="hidden" name="referral_code" value="{{ $referralResult['referrer']->referral_code }}">
                @endif
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" @disabled($gatewayMissing)>Bayar Sekarang</button>
                    <a href="{{ route('inayule.index') }}#beli" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
