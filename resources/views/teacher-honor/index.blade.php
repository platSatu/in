@extends('layouts.frontend')
@section('content')

{{--
    Honor Pengajar -- daftar periode per cabang. Honor = jumlah kelas x fee
    Course Class (1 kelas = 1 credit/paket), lihat TeacherHonorService.
--}}

<div class="middle-content container-xxl p-0">

    {{-- Judul halaman sudah tampil di breadcrumb layout (Dashboard / Honor Pengajar). --}}
    <p class="text-muted mb-3">Buat periode per cabang, honor pengajar akan terhitung otomatis dari kelas yang mereka ajar.</p>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-4 col-lg-5 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-1">Buat Periode Baru</h6>
                <p class="text-muted small mb-3">Tentukan tanggal cut-off-nya. Semua kelas yang diajar di rentang ini masuk ke honor periode tersebut.</p>

                <form method="POST" action="{{ route('teacher-honor.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Cabang</label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">Pilih cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $branchId) === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Periode</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', now()->translatedFormat('F Y')) }}" placeholder="September 2026" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tanggal Tutup</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}" required>
                        </div>
                        @error('end_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Buat Periode</button>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <form method="GET" action="{{ route('teacher-honor.index') }}" class="row g-2 mb-3">
                    <div class="col-md-6">
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Cabang</th>
                                <th>Tanggal</th>
                                <th>Total Honor</th>
                                <th>Status</th>
                                <th class="no-content text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $period)
                                <tr>
                                    <td class="fw-bold">{{ $period->name }}</td>
                                    <td>{{ optional($period->branch)->name ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}</td>
                                    <td class="text-nowrap">Rp {{ number_format((float) ($period->isOpen() ? ($openTotals[$period->id] ?? 0) : $period->honors_sum_honor_amount), 0, ',', '.') }}</td>
                                    <td>
                                        @if ($period->isOpen())
                                            <span class="badge badge-info">Sedang berjalan</span>
                                        @else
                                            <span class="badge badge-secondary">Ditutup</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('teacher-honor.show', $period->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">Lihat Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada periode. Yuk buat periode pertama di sebelah kiri!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $periods->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
