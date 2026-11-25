@extends('layouts.frontend')
@section('content')

{{--
    Course > Kode Referal (25 November 2026). Pengaturan + laporan pemakaian kode.
    Aturan pemakaian ada di App\Services\Referral\ReferralService.
--}}

@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
@endphp

<div class="middle-content container-xxl p-0">
    <div class="layout-top-spacing mb-3">
        <h5 class="mb-1">Kode Referal</h5>
        <p class="text-muted mb-0">Setiap akun punya kode referal sendiri. Kalau murid baru memakai kode teman saat membeli paket pertamanya, teman yang mengajak mendapat bonus saldo.</p>
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
        {{-- Disusun atas-bawah: Pengaturan, lalu ringkasan, lalu riwayat. --}}
        <div class="col-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
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
                        <div class="col-md-5 col-lg-4">
                            <label class="form-label">Bonus untuk pemberi kode</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="referrer_bonus_amount" class="form-control" min="0" step="1000"
                                    value="{{ old('referrer_bonus_amount', (int) $setting->referrer_bonus_amount) }}" placeholder="mis. 50000" @disabled(! $canEdit) required>
                            </div>
                            <div class="form-text">Ditulis tanpa titik, mis. 50000 untuk Rp 50.000.</div>
                        </div>
                    </div>

                    <div class="alert alert-light border small">
                        <strong class="d-block mb-1">Cara kerjanya</strong>
                        Misal bonus Rp 50.000: Budi membagikan kodenya ke Ani. Ani memasukkan kode Budi saat membeli paket pertamanya
                        dan membayar <strong>harga normal</strong>. Setelah pembayaran Ani lunas, Budi mendapat saldo <strong>Rp 50.000</strong>.
                        <ul class="mb-0 mt-2 ps-3">
                            <li>Murid baru <strong>tidak mendapat diskon</strong> dari kode referal. Kalau ingin memberi potongan, buat <strong>Voucher</strong> seperti biasa (tetap bisa dipakai bersamaan).</li>
                            <li>Hanya untuk <strong>pembelian pertama murid baru</strong>, sekali per murid.</li>
                            <li>Kode pemberi bisa dipakai banyak murid baru; bonus didapat <strong>1x dari tiap murid</strong>.</li>
                            <li>Bonus tidak pernah melebihi yang dibayar murid baru, dan tidak ada bonus kalau murid baru membayar Rp 0.</li>
                            <li>Saldo bonus <strong>hanya untuk membeli paket</strong>, tidak bisa dicairkan.</li>
                        </ul>
                    </div>

                    @if ($canEdit)
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    @endif
                </form>
            </div>
        </div>

        <div class="col-12 layout-spacing">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="widget-content widget-content-area br-8">
                        <p class="text-muted small mb-1">Murid baru dari referal</p>
                        <h5 class="mb-0">{{ $summary['buyers'] }} orang</h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="widget-content widget-content-area br-8">
                        <p class="text-muted small mb-1">Total bonus saldo</p>
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
                                <th class="text-end">Bonus</th>
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
                                        @if ((float) $row->discount_amount > 0)
                                            <div class="small text-success">hemat {{ $rupiah($row->discount_amount) }}</div>
                                        @endif
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
