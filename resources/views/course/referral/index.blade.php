@extends('layouts.frontend')
@section('content')

{{--
    Course > Kode Referal (25 November 2026). Pengaturan + laporan pemakaian kode.
    Aturan pemakaian ada di App\Services\Referral\ReferralService.
--}}

@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $pct = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

<div class="middle-content container-xxl p-0">
    <div class="layout-top-spacing mb-3">
        <h5 class="mb-1">Kode Referal</h5>
        <p class="text-muted mb-0">Setiap akun punya kode referal sendiri. Murid baru yang memakai kode teman mendapat diskon di pembelian pertamanya, dan pemberi kode mendapat komisi berupa saldo.</p>
    </div>

    @foreach (['success' => 'success', 'error' => 'danger'] as $flash => $type)
        @if (session($flash))
            <div class="alert alert-{{ $type }}">{{ session($flash) }}</div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-5 layout-spacing">
            <div class="widget-content widget-content-area br-8 h-100">
                <h6 class="mb-3">Pengaturan</h6>
                <form method="POST" action="{{ route('course.referral.settings') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                            @checked(old('is_active', $setting->is_active)) @disabled(! $canEdit)>
                        <label class="form-check-label" for="is_active">Program kode referal aktif</label>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Diskon pembeli baru (%)</label>
                            <input type="number" name="buyer_discount_percent" class="form-control" min="0" max="90" step="0.5"
                                value="{{ old('buyer_discount_percent', $pct($setting->buyer_discount_percent)) }}" @disabled(! $canEdit) required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Komisi pemberi kode (%)</label>
                            <input type="number" name="referrer_commission_percent" class="form-control" min="0" max="90" step="0.5"
                                value="{{ old('referrer_commission_percent', $pct($setting->referrer_commission_percent)) }}" @disabled(! $canEdit) required>
                        </div>
                    </div>

                    <div class="alert alert-light border small">
                        <strong class="d-block mb-1">Contoh dengan 10% + 10%</strong>
                        Paket Rp 1.000.000: murid baru membayar <strong>Rp 900.000</strong>. Setelah lunas, pemberi kode mendapat
                        saldo <strong>Rp 90.000</strong> (10% dari yang dibayar).
                        <ul class="mb-0 mt-2 ps-3">
                            <li>Hanya untuk <strong>pembelian pertama murid baru</strong>, sekali per murid.</li>
                            <li>Kode pemberi bisa dipakai banyak murid baru; komisi didapat <strong>1x dari tiap murid</strong>.</li>
                            <li>Bisa digabung voucher: diskon referal dihitung dari harga setelah voucher.</li>
                            <li>Saldo komisi <strong>hanya untuk membeli paket</strong>, tidak bisa dicairkan.</li>
                        </ul>
                    </div>

                    @if ($canEdit)
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    @endif
                </form>
            </div>
        </div>

        <div class="col-xl-7 layout-spacing">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="widget-content widget-content-area br-8">
                        <p class="text-muted small mb-1">Murid baru dari referal</p>
                        <h5 class="mb-0">{{ $summary['buyers'] }} orang</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-content widget-content-area br-8">
                        <p class="text-muted small mb-1">Total diskon pembeli</p>
                        <h5 class="mb-0">{{ $rupiah($summary['discount']) }}</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-content widget-content-area br-8">
                        <p class="text-muted small mb-1">Total komisi saldo</p>
                        <h5 class="mb-0">{{ $rupiah($summary['commission']) }}</h5>
                    </div>
                </div>
            </div>

            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-3">Riwayat Pemakaian Kode</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pemberi Kode</th>
                                <th>Murid Baru</th>
                                <th>Paket</th>
                                <th class="text-end">Dibayar</th>
                                <th class="text-end">Komisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($redemptions as $row)
                                <tr>
                                    <td class="text-nowrap small">{{ $row->created_at?->translatedFormat('d M Y, H:i') }}</td>
                                    <td>
                                        {{ $row->referrer?->name ?? '-' }}
                                        <div class="small text-muted">{{ $row->code }}</div>
                                    </td>
                                    <td>
                                        {{ $row->buyer?->name ?? '-' }}
                                        <div class="small text-muted">{{ $row->buyer?->email }}</div>
                                    </td>
                                    <td class="small">{{ $row->coursePackage?->name ?? '-' }}</td>
                                    <td class="text-end text-nowrap">
                                        {{ $rupiah($row->price_after) }}
                                        <div class="small text-success">hemat {{ $rupiah($row->discount_amount) }}</div>
                                    </td>
                                    <td class="text-end text-nowrap">{{ $row->commission_paid_at ? $rupiah($row->commission_amount) : '-' }}</td>
                                    <td><span class="badge {{ $row->commission_paid_at ? 'badge-success' : 'badge-secondary' }}">{{ $row->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada murid baru yang memakai kode referal.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $redemptions->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
