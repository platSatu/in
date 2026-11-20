@extends('layouts.frontend')
@section('content')

{{-- Course > Voucher Paket: tambah / edit voucher (20 November 2026). Lihat CourseVoucherController. --}}
@php
    $isEdit = $voucher->exists;
    $selectedPackages = old('course_package_ids', $voucher->course_package_ids ?? []);
    $type = old('discount_type', $voucher->discount_type);
@endphp

<div class="middle-content container-xxl p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 layout-top-spacing mb-3">
        <h5 class="mb-0">{{ $isEdit ? 'Edit Voucher '.$voucher->code : 'Tambah Voucher Paket' }}</h5>
        <a href="{{ route('course.voucher.index') }}" class="small">&larr; Kembali ke daftar voucher</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">Voucher belum tersimpan:</div>
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="widget-content widget-content-area br-8 mb-4">
        <form action="{{ $isEdit ? route('course.voucher.update', $voucher->id) : route('course.voucher.store') }}" method="POST" onsubmit="this.querySelector('[type=submit]').disabled = true;">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kode Voucher</label>
                    <input type="text" name="code" maxlength="50" class="form-control text-uppercase @error('code') is-invalid @enderror"
                        value="{{ old('code', $voucher->code) }}" placeholder="mis. MURIDLAMA10" required>
                    <div class="form-text">Huruf, angka, - atau _. Murid mengetik kode ini saat checkout.</div>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Nama / Keterangan</label>
                    <input type="text" name="name" maxlength="255" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $voucher->name) }}" placeholder="mis. Diskon murid lama akhir tahun" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipe Diskon</label>
                    <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                        @foreach (\App\Models\CourseVoucher::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Besar Diskon</label>
                    <div class="input-group">
                        <span class="input-group-text" id="discount_prefix">Rp</span>
                        <input type="number" name="discount_value" min="1" step="any" class="form-control @error('discount_value') is-invalid @enderror"
                            value="{{ old('discount_value', $voucher->discount_value !== null ? (float) $voucher->discount_value : '') }}" required>
                        <span class="input-group-text" id="discount_suffix">%</span>
                    </div>
                    <div class="form-text" id="discount_help"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kuota (berapa kali bisa dipakai)</label>
                    <input type="number" name="quota" min="1" class="form-control @error('quota') is-invalid @enderror"
                        value="{{ old('quota', $voucher->quota) }}" placeholder="mis. 50" required>
                    <div class="form-text">
                        Total pemakaian semua murid. 1 akun hanya bisa memakai kode ini 1 kali.
                        @if ($isEdit) Terpakai saat ini: <b>{{ $used }}</b>. @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Berlaku Mulai</label>
                    <input type="date" name="valid_from" class="form-control @error('valid_from') is-invalid @enderror"
                        value="{{ old('valid_from', $voucher->valid_from?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Berlaku Sampai</label>
                    <input type="date" name="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                        value="{{ old('valid_until', $voucher->valid_until?->format('Y-m-d')) }}" required>
                    <div class="form-text">Berlaku sampai pukul 23:59 di tanggal ini.</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $voucher->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $voucher->status) === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="border rounded p-3 mb-3">
                <input type="hidden" name="only_eligible_students" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="only_eligible_students" name="only_eligible_students" value="1"
                        @checked(old('only_eligible_students', $voucher->only_eligible_students))>
                    <label class="form-check-label fw-bold" for="only_eligible_students">Khusus murid yang diizinkan</label>
                </div>
                <div class="form-text">
                    ON: hanya murid dengan switch <b>"Bisa pakai voucher"</b> menyala di Course &rarr; Student InaYule
                    (otomatis menyala untuk murid lama dari Excel yang punya join_by_date). OFF: semua murid boleh memakai kode ini.
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Berlaku untuk Paket</label>
                <div class="form-text mt-0 mb-2">Tidak dicentang sama sekali = berlaku untuk <b>semua paket</b>. Hanya untuk beli paket baru (tidak untuk upgrade).</div>
                <div class="row g-2">
                    @foreach ($packages as $package)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="course_package_ids[]" value="{{ $package->id }}" id="pkg-{{ $package->id }}"
                                    @checked(in_array($package->id, $selectedPackages, true))>
                                <label class="form-check-label" for="pkg-{{ $package->id }}">
                                    {{ $package->name }}
                                    <span class="text-muted small">· Rp {{ number_format((float) $package->price, 0, ',', '.') }}{{ $package->status !== 'active' ? ' · nonaktif' : '' }}</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Voucher</button>
                <a href="{{ route('course.voucher.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

    @if ($isEdit)
        <div class="widget-content widget-content-area br-8">
            <h6 class="fw-bold mb-3">Riwayat Pemakaian</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Waktu</th><th>Murid</th><th>Paket</th><th class="text-end">Harga Awal</th><th class="text-end">Diskon</th><th class="text-end">Dibayar</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($redemptions as $redemption)
                            <tr>
                                <td class="small text-nowrap">{{ $redemption->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td>{{ trim(($redemption->student->first_name ?? '').' '.($redemption->student->last_name ?? '')) ?: '-' }}
                                    <div class="small text-muted">{{ $redemption->student->email ?? '' }}</div></td>
                                <td>{{ $redemption->coursePackage->name ?? '-' }}</td>
                                <td class="text-end">Rp {{ number_format((float) $redemption->price_before, 0, ',', '.') }}</td>
                                <td class="text-end text-success">- Rp {{ number_format((float) $redemption->discount_amount, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format((float) $redemption->price_after, 0, ',', '.') }}</td>
                                <td class="small">{{ $redemption->statusLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Voucher ini belum pernah dipakai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-muted">Pembayaran yang gagal/kedaluwarsa otomatis tidak dihitung kuota, dan murid itu boleh mencoba lagi.</div>
        </div>
    @endif
</div>

<script>
    (function () {
        var select = document.getElementById('discount_type');
        var prefix = document.getElementById('discount_prefix');
        var suffix = document.getElementById('discount_suffix');
        var help = document.getElementById('discount_help');
        function sync() {
            var percent = select.value === 'percent';
            prefix.hidden = percent;
            suffix.hidden = !percent;
            help.textContent = percent ? 'Contoh: 10 = diskon 10% dari harga paket (maksimal 99%).' : 'Contoh: 50000 = potongan Rp 50.000 dari harga paket.';
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
