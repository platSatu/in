@extends('layouts.frontend')
@section('content')

{{--
    Upgrade / Convert dari 1 baris paket (lihat PackageUpgradeCalculator).
    Halaman ini hanya tampilan; semua angka dihitung ulang di server saat submit.
    - Upgrade paket penuh: semua sisa credit ditukar ke 1 paket utuh, kurang dibayar / lebih ke saldo.
    - Convert sebagian: credit lama dipotong pas, sisanya tetap credit.
    - Tukar semua: semua sisa credit ditukar, sisa nilai < 1 credit tujuan masuk saldo.
--}}
@php
    $rp = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $num = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $sourceName = $source->coursePackage->name ?? 'paket lama';
    $sourceClass = $source->coursePackage->courseClass->name ?? '-';
@endphp

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Upgrade / Convert Paket</h4>
            <p class="text-muted mb-3">
                Dari: <strong>{{ $sourceName }}</strong>
                ({{ $sourceClass }}) &middot; sisa {{ $num($remaining) }} credit
            </p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($blockedReason)
                {{-- Tidak bisa ditukar: tampilkan alasannya saja, tanpa pilihan yang bisa diklik. --}}
                <div class="alert alert-warning">{{ $blockedReason }}</div>
                <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Kembali</a>
            @else
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
                                <strong>Upgrade paket penuh</strong> &mdash; semua sisa credit ditukar ke 1 paket utuh. Kalau kurang, dibayar pakai saldo lalu payment gateway; kalau lebih, sisanya masuk saldo.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="mode-convert" value="convert" @checked($mode === 'convert') onchange="this.form.submit()">
                            <label class="form-check-label" for="mode-convert">
                                <strong>Convert credit</strong> &mdash; pilih mau ambil berapa credit paket tujuan. Sisa credit lama tetap bisa dipakai.
                            </label>
                        </div>
                    </div>

                    @if ($mode === 'convert' && $target && $sourceUnit > 0 && $targetUnit > 0)
                        @php
                            $targetName = $target->courseClass->name ?? $target->name;
                            $ratio = $targetUnit / $sourceUnit;
                        @endphp
                        <div class="alert alert-light border small mb-3">
                            <div class="fw-semibold mb-1">Cara hitungnya</div>
                            1 credit {{ $sourceClass }} = {{ $rp($sourceUnit) }} &middot; 1 credit {{ $targetName }} = {{ $rp($targetUnit) }}<br>
                            @if ($ratio >= 1)
                                Jadi <strong>{{ $num($ratio) }} credit {{ $sourceClass }} = 1 credit {{ $targetName }}</strong>.
                            @else
                                Jadi <strong>1 credit {{ $sourceClass }} = {{ $num(1 / $ratio) }} credit {{ $targetName }}</strong>.
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mau ambil berapa credit {{ $targetName }}?</label>
                            <div class="input-group">
                                <input type="number" name="quantity" class="form-control" min="1" step="1"
                                    @if (isset($preview['max_quantity'])) max="{{ $preview['max_quantity'] }}" @endif
                                    value="{{ $quantity }}">
                                <button type="submit" class="btn btn-outline-secondary">Hitung</button>
                                <button type="submit" name="all" value="1" class="btn btn-outline-primary">Tukar Semua</button>
                            </div>
                            @if (isset($preview['max_quantity']))
                                <div class="form-text">
                                    Maksimal <strong>{{ $preview['max_quantity'] }} credit</strong>. Pilih <strong>Tukar Semua</strong> untuk menukar seluruh sisa credit;
                                    sisa nilai yang tidak cukup untuk 1 credit akan masuk saldo Anda.
                                </div>
                            @endif
                        </div>
                    @endif
                </form>

                @if ($preview)
                    {{-- Langkah 2: rincian --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between py-1">
                            <span>{{ $mode === 'convert' ? 'Credit yang Anda dapat' : 'Paket baru' }}</span>
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
                        @if ($preview['source_after'] > 0)
                            <div class="d-flex justify-content-between py-1 text-success">
                                <span>Sisa credit {{ $sourceName }} (tetap bisa dipakai)</span>
                                <span>{{ $num($preview['source_after']) }} credit</span>
                            </div>
                        @endif
                        @if ($preview['leftover_to_deposit'] > 0)
                            <div class="d-flex justify-content-between py-1 text-success">
                                <span>Sisa nilai masuk saldo Anda</span>
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
                        Credit lama tidak dicairkan tunai. Saldo hanya bisa dipakai untuk membeli paket lagi.
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
                            @if ($convertAll)
                                <input type="hidden" name="all" value="1">
                            @else
                                <input type="hidden" name="quantity" value="{{ $quantity }}">
                            @endif
                        @endif
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" @disabled($gatewayMissing)>
                                {{ $mode === 'convert' ? ($convertAll ? 'Tukar Semua Sekarang' : 'Convert Sekarang') : ($preview['gateway_portion'] > 0 ? 'Lanjut ke Pembayaran' : 'Upgrade Sekarang') }}
                            </button>
                            <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                @else
                    <a href="{{ route('inayule.index') }}" class="btn btn-outline-secondary">Kembali</a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
