@extends('layouts.frontend')
@section('content')

{{--
    Detail 1 periode Honor Pengajar: per pengajar -> kelas (1 kelas = 1
    credit/paket) -> siswa, jam & credit yang terpotong. Periode terbuka
    dihitung langsung, periode ditutup memakai rekap yang sudah dikunci.
    Lihat TeacherHonorService.
--}}

@php
    $honorStatus = [
        'pending' => ['Menunggu persetujuan', 'badge-warning'],
        'approved_for_payout' => ['Disetujui, menunggu dibayar', 'badge-info'],
        'paid' => ['Sudah dibayar', 'badge-success'],
    ];
    $canEdit = auth()->user()?->canAccessPermission('teacher-honor', 'edit') ?? false;
@endphp

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <a href="{{ route('teacher-honor.index') }}" class="small text-muted">&larr; Kembali ke daftar periode</a>
            <h5 class="mb-1 mt-1">{{ $period->name }} &middot; {{ optional($period->branch)->name ?? '-' }}</h5>
            <p class="text-muted mb-0">
                {{ $period->start_date->translatedFormat('d F Y') }} &ndash; {{ $period->end_date->translatedFormat('d F Y') }}
                @if ($period->isOpen())
                    <span class="badge badge-info ms-1">Sedang berjalan</span>
                @else
                    <span class="badge badge-secondary ms-1">Ditutup {{ optional($period->closed_at)->format('d/m/Y H:i') }}</span>
                @endif
            </p>
        </div>

        @if ($canEdit && $period->isOpen())
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('teacher-honor.close', $period->id) }}"
                    onsubmit="return confirm('Tutup periode ini? Setelah ditutup, rekap dikunci dan sesi baru tidak masuk lagi ke periode ini.')">
                    @csrf
                    <button type="submit" class="btn btn-primary">Tutup Periode</button>
                </form>
                <form method="POST" action="{{ route('teacher-honor.destroy', $period->id) }}"
                    onsubmit="return confirm('Hapus periode ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Hapus</button>
                </form>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($period->isOpen())
        <div class="alert alert-light border mb-3">
            Angka di bawah masih bisa bertambah selama periode berjalan. Tutup periode kalau sudah cut-off supaya honornya bisa disetujui.
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Pengajar</p>
                <h5 class="mb-0">{{ $recap->count() }} orang</h5>
            </div>
        </div>
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Total Kelas</p>
                <h5 class="mb-0">{{ $recap->sum('class_count') }} kelas</h5>
            </div>
        </div>
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Total Honor</p>
                <h5 class="mb-0">Rp {{ number_format((float) $recap->sum('honor_amount'), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>

    @forelse ($recap as $row)
        <div class="widget-content widget-content-area br-8 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h6 class="mb-1">{{ $row['teacher_name'] }}</h6>
                    <p class="text-muted mb-0">{{ $row['class_count'] }} kelas &middot; <strong>Rp {{ number_format((float) $row['honor_amount'], 0, ',', '.') }}</strong></p>
                </div>

                @if ($row['honor'])
                    @php $status = $honorStatus[$row['honor']->status] ?? [$row['honor']->status, 'badge-secondary']; @endphp
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $status[1] }}">{{ $status[0] }}</span>
                        @if ($canEdit && $row['honor']->status === 'pending')
                            <form method="POST" action="{{ route('teacher-honor.approve-payout', $row['honor']->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">Setujui</button>
                            </form>
                        @elseif ($canEdit && $row['honor']->status === 'approved_for_payout')
                            <form method="POST" action="{{ route('teacher-honor.mark-paid', $row['honor']->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Tandai Dibayar</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @include('teacher-honor._classes', ['classes' => $row['classes']])
        </div>
    @empty
        <div class="widget-content widget-content-area br-8 text-center text-muted py-5">
            Belum ada kelas di periode ini. Honor akan muncul otomatis begitu ada sesi yang disetujui di cabang ini.
        </div>
    @endforelse
</div>
@endsection
