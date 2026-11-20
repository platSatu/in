@extends('layouts.frontend')
@section('content')

{{-- Course > Voucher Paket (20 November 2026): voucher diskon beli paket credit InaYule. Lihat CourseVoucherController. --}}
<div class="middle-content container-xxl p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 layout-top-spacing mb-3">
        <h5 class="mb-0">Voucher Paket</h5>
        @if ($canEdit)
            <a href="{{ route('course.voucher.create') }}" class="btn btn-primary">+ Tambah Voucher</a>
        @endif
    </div>

    @foreach (['success' => 'success', 'error' => 'danger'] as $flash => $type)
        @if (session($flash))
            <div class="alert alert-{{ $type }} alert-dismissible fade show">
                {{ session($flash) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    <div class="row">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <form method="GET" action="{{ route('course.voucher.index') }}" class="row g-2 mb-4">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" maxlength="100" placeholder="Cari kode atau nama voucher..." value="{{ $search }}">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary">Cari</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table dt-table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode / Nama</th>
                                <th>Diskon</th>
                                <th>Kuota Terpakai</th>
                                <th>Masa Berlaku</th>
                                <th>Untuk</th>
                                <th>Status</th>
                                @if ($canEdit)<th class="text-center">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vouchers as $index => $voucher)
                                @php
                                    $expired = $voucher->valid_until->isPast();
                                    $notStarted = $voucher->valid_from->isFuture();
                                    $full = $voucher->used >= $voucher->quota;
                                @endphp
                                <tr>
                                    <td>{{ $vouchers->firstItem() + $index }}</td>
                                    <td>
                                        <span class="badge badge-light-primary fs-6" style="letter-spacing:.05em;">{{ $voucher->code }}</span>
                                        <div class="small text-muted mt-1">{{ $voucher->name }}</div>
                                    </td>
                                    <td class="fw-bold text-nowrap">{{ $voucher->discountLabel() }}</td>
                                    <td class="text-nowrap">
                                        <span @class(['fw-bold', 'text-danger' => $full])>{{ $voucher->used }} / {{ $voucher->quota }}</span>
                                        @if ($full)<div class="small text-danger">Kuota habis</div>@endif
                                    </td>
                                    <td class="small text-nowrap">
                                        {{ $voucher->valid_from->translatedFormat('d M Y') }} – {{ $voucher->valid_until->translatedFormat('d M Y') }}
                                        @if ($expired)<div class="text-danger">Sudah berakhir</div>@elseif ($notStarted)<div class="text-warning">Belum mulai</div>@endif
                                    </td>
                                    <td class="small">
                                        <div>{{ $voucher->only_eligible_students ? 'Murid yang diizinkan' : 'Semua murid' }}</div>
                                        <div class="text-muted">{{ empty($voucher->course_package_ids) ? 'Semua paket' : count($voucher->course_package_ids).' paket' }}</div>
                                    </td>
                                    <td>
                                        @if ($voucher->status === \App\Models\CourseVoucher::STATUS_ACTIVE)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    @if ($canEdit)
                                        <td class="text-center">
                                            <div class="d-flex flex-nowrap justify-content-center gap-2">
                                                <a href="{{ route('course.voucher.edit', $voucher->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form action="{{ route('course.voucher.destroy', $voucher->id) }}" method="POST" class="m-0"
                                                    onsubmit="return confirm('Hapus voucher {{ $voucher->code }}? Kalau sudah pernah dipakai, voucher hanya dinonaktifkan.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canEdit ? 8 : 7 }}" class="text-center text-muted py-4">
                                        {{ $search !== '' ? 'Tidak ada voucher yang cocok.' : 'Belum ada voucher paket.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $vouchers->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
