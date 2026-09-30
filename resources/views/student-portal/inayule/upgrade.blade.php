@extends('layouts.frontend')
@section('content')

{{--
    Upgrade / Convert dari 1 baris paket (lihat PackageUpgradeCalculator).
    Halaman ini hanya tampilan; semua angka dihitung ulang di server saat submit.
--}}
@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $num = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Upgrade / Convert Paket</h4>
            <p class="text-muted mb-3">
                Dari: <strong>{{ $source->coursePackage->name ?? '-' }}</strong>
                ({{ $source->coursePackage->courseClass->name ?? '-' }}) &middot; sisa {{ $num($remaining) }} credit
            </p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($blockedReason)
                <div class="alert alert-warning">{{ $blockedReason }}</div>
            @endif

            {{-- Langkah 1: pilih paket tujuan & cara --}}
            <form method="GET" action="{{ route('inayule.upgrade.show', $source->id) }}" class="mb-4">
                <div class="mb-3">
                    <label class="form-label">Paket Tujuan</label>
                    <select name="package" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Pilih Paket --</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected($target?->id === $package->id)>
                                {{ $package->name }} ({{ $package->courseClass->name ?? '-' }}) &middot; {{ $rp($package->effectivePrice()) }} / {{ $num($package->credits) }} credit
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode-upgrade" value="upgrade" @checked($mode === 'upgrade') onchange="this.form.submit()">
                        <label class="form-check-label" for="mode-upgrade">
                            <strong>Upgrade paket penuh</strong> &mdash; semua sisa credit ditukar, kekurangannya dibayar saldo lalu payment gateway.
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode-convert" value="convert" @checked($mode === 'convert') onchange="this.form.submit()">
                        <label class="form-check-label" for="mode-convert">
                            <strong>Convert sebagian credit</strong> &mdash; pilih mau ambil berapa credit paket tujuan.
                        </label>
                    </div>
                </div>

                @if ($mode === 'convert' && $target)
                    <div class="mb-3">
                        <label class="form-label">Mau ambil berapa credit?</label>
                        <div class="input-group">
                            <input type="number" name="quantity" class="form-control" min="1" step="1"
                                @if (isset($preview['max_quantity'])) max="{{ $preview['max_quantity'] }}" @endif
                                value="{{ $quantity }}" required>
                            <button type="submit" class="btn btn-outline-secondary">Hitung</button>
                        </div>
                        @if (isset($preview['max_quantity']))
                            <div class="form-text">Anda bisa mengambil maksimal <strong>{{ $preview['max_quantity'] }} credit</strong> dari sisa credit paket ini.</div>
                        @endif
                    </div>
                @endif
            </form>

            @if ($preview)
                {{-- Langkah 2: rincian --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between py-1">
                        <span>{{ $mode === 'convert' ? 'Credit yang diambil' : 'Paket baru' }}</span>
                        <span class="fw-bold">{{ $num($preview['quantity']) }} credit &middot; {{ $rp($preview['price']) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between py-1 text-muted">
                        <span>Credit lama yang ditukar</span>
                        <span>{{ $num($preview['credits_used']) }} dari {{ $num($preview['source_remaining']) }} credit</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span>Nilai tukar credit lama</span>
                        <span>{{ $rp($preview['trade_in_value']) }}</span>
                    </div>
                    @if ($preview['leftover_to_deposit'] > 0)
                        <div class="d-flex justify-content-between py-1 text-success">
                            <span>Kelebihan masuk saldo Anda</span>
                            <span>{{ $rp($preview['leftover_to_deposit']) }}</span>
                        </div>
                    @endif
                    @if ($preview['shortfall'] > 0)
                        <hr>
                        <div class="d-flex justify-content-between py-1">
                            <span>Dibayar pakai saldo</span>
                            <span>{{ $rp($preview['deposit_portion']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Sisa via payment gateway</span>
                            <span class="fw-bold">{{ $rp($preview['gateway_portion']) }}</span>
                        </div>
                    @endif
                </div>

                <div class="alert alert-info small">
                    Credit lama tidak dicairkan tunai. Kelebihan nilainya masuk ke saldo Anda dan hanya bisa dipakai untuk membeli paket lagi.
                    @if ($mode === 'convert' && $preview['source_remaining'] > $preview['credits_used'])
                        Sisa {{ $num($preview['source_remaining'] - $preview['credits_used']) }} credit paket lama tetap bisa Anda pakai.
                    @endif
                </div>

                @if ($gatewayMissing)
                    <div class="alert alert-warning">Payment gateway belum diaktifkan oleh admin, jadi sisa tagihan di atas tidak bisa diproses. Silakan hubungi admin.</div>
                @endif

                <form method="POST" action="{{ route('inayule.upgrade.store', $source->id) }}"
                    onsubmit="return confirm('Lanjutkan {{ $mode === 'convert' ? 'convert' : 'upgrade' }}? Credit lama yang ditukar tidak bisa dikembalikan.')">
                    @csrf
                    <input type="hidden" name="package" value="{{ $target->id }}">
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    @if ($mode === 'convert')
                        <input type="hidden" name="quantity" value="{{ $quantity }}">
                    @endif
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" @disabled($gatewayMissing || $blockedReason)>
                            {{ $mode === 'convert' ? 'Convert Sekarang' : ($preview['gateway_portion'] > 0 ? 'Lanjut ke Pembayaran' : 'Upgrade Sekarang') }}
                        </button>
                        <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            @else
                <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Kembali</a>
            @endif
        </div>
    </div>
</div>
@endsection
