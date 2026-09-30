@extends('layouts.frontend')
@section('content')

{{-- Riwayat mutasi credit per siswa (hanya baca). Lihat ClassSessionAdminController::creditHistory(). --}}
<div class="middle-content container-xxl p-0">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="mb-0">Riwayat Credit Siswa</h6>
                    <a href="{{ route('class-session.index') }}" class="btn btn-sm btn-outline-secondary">Kembali ke Pengajuan Kelas</a>
                </div>

                <form method="GET" action="{{ route('class-session.credit-history') }}" class="row g-2 mb-4">
                    <div class="col-md-6">
                        <select name="student" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($students as $item)
                                <option value="{{ $item->id }}" @selected($student?->id === $item->id)>{{ trim($item->first_name . ' ' . $item->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if ($student)
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @forelse ($packages as $package)
                            <span class="badge badge-light-primary p-2">
                                {{ $package['name'] }}: sisa {{ rtrim(rtrim(number_format((float) $package['remaining'], 2, ',', '.'), '0'), ',') }} credit
                            </span>
                        @empty
                            <span class="text-muted small">Siswa ini belum punya paket.</span>
                        @endforelse
                    </div>

                    @include('course-credit._ledger', ['ledger' => $ledger])
                @else
                    <p class="text-muted mb-0">Pilih siswa untuk melihat semua mutasi credit-nya: beli paket, kelas, refund, potong oleh admin, dan tukar paket.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
