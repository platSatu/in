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
            <h5 class="mb-1">{{ $period->name }} &middot; {{ optional($period->branch)->name ?? '-' }}</h5>
            <p class="text-muted mb-0">
                {{ $period->start_date->translatedFormat('d F Y') }} - {{ $period->end_date->translatedFormat('d F Y') }}
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
                    <button type="submit" class="btn btn-primary" @disabled($closeBlockedReason) title="{{ $closeBlockedReason }}">Tutup Periode</button>
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
        @if (today()->greaterThan($period->end_date) && $closeBlockedReason)
            {{-- Sudah lewat cut-off tapi masih ada pengajuan kelas yang belum selesai. --}}
            <div class="alert alert-warning mb-3">
                ⚠️ {{ $closeBlockedReason }}
                @if (auth()->user()?->canAccessPermission('class-session'))
                    <a href="{{ route('class-session.index') }}" class="alert-link ms-1">Lihat pengajuan</a>
                @endif
            </div>
        @else
            <div class="alert alert-light border mb-3">
                Angka di bawah masih bisa bertambah selama periode berjalan.
                {{ $closeBlockedReason ?? 'Periode sudah lewat cut-off dan semua pengajuan kelas sudah selesai, sekarang bisa ditutup supaya honornya bisa disetujui.' }}
            </div>
        @endif
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
                <p class="text-muted mb-1">Total Kelas &amp; Jam Mengajar</p>
                <h5 class="mb-0">{{ $recap->sum('class_count') }} kelas &middot; {{ \App\Models\ClassSession::hoursLabel((float) $recap->sum('teaching_hours')) }}</h5>
            </div>
        </div>
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Total Honor</p>
                <h5 class="mb-0">Rp {{ number_format((float) $recap->sum('honor_amount'), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>

    {{-- Filter cabang & pengajar + Export Excel (25 November 2026). --}}
    <form method="GET" class="widget-content widget-content-area br-8 mb-3 d-flex flex-wrap gap-2">
        <div>
            <label class="form-label small text-muted mb-1">Cabang</label>
            <select class="form-select" onchange="if (this.value) window.location = this.value">
                @foreach ($branchPeriods as $item)
                    <option value="{{ route('teacher-honor.show', $item->id) }}" @selected($item->id === $period->id)>{{ $item->branch?->name ?? '-' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small text-muted mb-1">Pengajar</label>
            <select name="teacher" class="form-select" onchange="this.form.submit()">
                <option value="">Semua pengajar</option>
                @foreach ($teacherOptions as $teacher)
                    <option value="{{ $teacher['id'] }}" @selected($teacherFilter === $teacher['id'])>{{ $teacher['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-grow-1" style="min-width: 200px">
            <label class="form-label small text-muted mb-1">Cari pengajar</label>
            <input type="search" name="q" class="form-control" placeholder="Ketik nama pengajar lalu Enter..." value="{{ $search }}" autocomplete="off">
        </div>
        <div>
            <label class="form-label small text-muted mb-1">Urutkan</label>
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="name" @selected($sort === 'name')>Nama (A-Z)</option>
                <option value="honor" @selected($sort === 'honor')>Honor terbesar</option>
                <option value="hours" @selected($sort === 'hours')>Jam terbanyak</option>
            </select>
        </div>
        {{-- Label kosong + flex-grow supaya tinggi tombol sama persis dengan kolom di sebelahnya. --}}
        <div class="d-flex flex-column">
            <label class="form-label small mb-1 invisible" aria-hidden="true">Export</label>
            <a href="{{ route('teacher-honor.export', ['id' => $period->id, 'teacher' => $teacherFilter, 'q' => $search ?: null]) }}"
                class="btn btn-success flex-grow-1 d-flex align-items-center justify-content-center px-4">
                Export Excel
            </a>
        </div>
    </form>

    {{--
        Banyak pengajar & murid: ringkasan 1 baris per pengajar dulu, rincian kelas & murid baru dibuka
        saat diklik ("Rincian"), supaya halaman tidak memanjang. Kalau hanya 1 pengajar, rincian langsung terbuka.
    --}}
    @if ($recap->isEmpty() && ($search !== '' || $teacherFilter))
        <div class="widget-content widget-content-area br-8 text-center text-muted py-5">
            Tidak ada pengajar yang cocok dengan pencarian/filter ini.
        </div>
    @elseif ($recap->isEmpty())
        <div class="widget-content widget-content-area br-8 text-center text-muted py-5">
            Belum ada kelas di periode ini. Honor akan muncul otomatis begitu ada sesi yang disetujui di cabang ini.
        </div>
    @else
        <div class="widget-content widget-content-area br-8">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pengajar</th>
                            <th class="text-center">Kelas</th>
                            <th>Jam Mengajar</th>
                            <th class="text-end">Honor</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $index => $row)
                            @php
                                $status = $row['honor'] ? ($honorStatus[$row['honor']->status] ?? [$row['honor']->status, 'badge-secondary']) : ['Sedang berjalan', 'badge-light-info'];
                                $openByDefault = $recap->count() === 1;
                                $detailId = 'honorDetail'.$rows->currentPage().'-'.$index;
                            @endphp
                            <tr class="honor-row" data-name="{{ \Illuminate\Support\Str::lower($row['teacher_name']) }}">
                                <td class="fw-bold">{{ $row['teacher_name'] }}</td>
                                <td class="text-center">{{ $row['class_count'] }}</td>
                                <td class="text-nowrap">{{ \App\Models\ClassSession::hoursLabel((float) $row['teaching_hours']) }}</td>
                                <td class="text-end text-nowrap fw-bold">Rp {{ number_format((float) $row['honor_amount'], 0, ',', '.') }}</td>
                                <td><span class="badge {{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        @if ($canEdit && $row['honor'] && $row['honor']->status === 'pending')
                                            <form method="POST" action="{{ route('teacher-honor.approve-payout', $row['honor']->id) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">Setujui</button>
                                            </form>
                                        @elseif ($canEdit && $row['honor'] && $row['honor']->status === 'approved_for_payout')
                                            <form method="POST" action="{{ route('teacher-honor.mark-paid', $row['honor']->id) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Tandai Dibayar</button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-bs-toggle="collapse"
                                            data-bs-target="#{{ $detailId }}" aria-expanded="{{ $openByDefault ? 'true' : 'false' }}">
                                            Rincian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="honor-detail" data-name="{{ \Illuminate\Support\Str::lower($row['teacher_name']) }}">
                                <td colspan="6" class="p-0 border-0">
                                    <div class="collapse {{ $openByDefault ? 'show' : '' }}" id="{{ $detailId }}">
                                        {{-- Murid banyak: rincian dibatasi tingginya & bisa digulir di dalam kotak. --}}
                                        <div class="p-3 bg-light rounded-3 my-2" style="max-height: 460px; overflow-y: auto;">
                                            @include('teacher-honor._classes', ['classes' => $row['classes']])
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small class="text-muted">Menampilkan {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }} dari {{ $rows->total() }} pengajar</small>
                {{ $rows->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
