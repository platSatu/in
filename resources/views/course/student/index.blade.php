@extends('layouts.frontend')
@section('content')

{{-- Course > Student InaYule: murid yang punya paket, sisa credit & saldo, pengajar tetap. Lihat CourseStudentController. --}}
@php
    $creditNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

<div class="middle-content container-xxl p-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 layout-top-spacing mb-3">
        <h5 class="mb-0">Student InaYule</h5>
        @if ($canEdit)
            <div class="d-flex gap-2">
                <a href="{{ route('course.student.import.template') }}" class="btn btn-sm btn-outline-secondary">Unduh Contoh Excel</a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#inayule-import">Import Excel</button>
            </div>
        @endif
    </div>

    @foreach (['success' => 'success', 'error' => 'danger'] as $flash => $type)
        @if (session($flash))
            <div class="alert alert-{{ $type }}">{{ session($flash) }}</div>
        @endif
    @endforeach
    @if (session('import_failed'))
        <div class="alert alert-warning">
            <strong>Beberapa murid gagal diimport:</strong>
            <ul class="mb-0">@foreach (session('import_failed') as $failed)<li>{{ $failed }}</li>@endforeach</ul>
        </div>
    @endif
    @error('file') <div class="alert alert-danger">{{ $message }}</div> @enderror
    @error('branch_id') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="row g-3 mb-3">
        @foreach ([
            ['Student InaYule', number_format($summary['students'], 0, ',', '.'), null],
            ['Total Sisa Credit', $creditNum($summary['credits']), null],
            ['Credit Hampir Habis (≤ ' . $lowCredit . ')', number_format($summary['low'], 0, ',', '.'), 'low'],
            ['Credit Habis', number_format($summary['empty'], 0, ',', '.'), 'empty'],
        ] as [$label, $value, $credit])
            <div class="col-6 col-md-3">
                <div class="widget-content widget-content-area br-8 h-100">
                    <div class="text-muted mb-1" style="font-size:13px;">{{ $label }}</div>
                    <div class="fw-bold" style="font-size:24px;">{{ $value }}</div>
                    @if ($credit)
                        <a href="{{ route('course.student.index', ['credit' => $credit]) }}" class="small">Lihat murid</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <form method="GET" action="{{ route('course.student.index') }}" class="row g-2 mb-4">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" maxlength="100"
                            placeholder="Cari nama, email, atau HP murid..." value="{{ $filters['search'] }}">
                    </div>
                    <div class="col-md-3">
                        <select name="teacher" class="form-select">
                            <option value="">-- Semua Pengajar --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(($filters['teacher'] ?? null) === $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="credit" class="form-select">
                            <option value="">-- Semua Credit --</option>
                            <option value="low" @selected(($filters['credit'] ?? null) === 'low')>Hampir habis (≤ {{ $lowCredit }})</option>
                            <option value="empty" @selected(($filters['credit'] ?? null) === 'empty')>Habis</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-outline-primary flex-grow-1">Cari</button>
                        @if (array_filter($filters))
                            <a href="{{ route('course.student.index') }}" class="btn btn-outline-secondary" title="Hapus filter">×</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table dt-table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Student</th>
                                <th>Pengajar Tetap</th>
                                <th>Paket Aktif</th>
                                <th class="text-end">Sisa Credit</th>
                                <th class="text-end">Sisa Saldo</th>
                                <th>Aktivitas Terakhir</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $index => $student)
                                @php($balance = (float) $student->credit_balance)
                                <tr>
                                    <td>{{ $students->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                        <a href="{{ route('course.student.show', $student->id) }}">{{ trim($student->first_name . ' ' . $student->last_name) ?: '-' }}</a>
                                        <div class="text-muted fw-normal" style="font-size:12px;">{{ $student->email ?: '-' }}</div>
                                        <div class="text-muted fw-normal" style="font-size:12px;">{{ $student->handphone ?: '-' }}</div>
                                    </td>
                                    <td>{{ $student->teacher?->name ?? '-' }}</td>
                                    <td>
                                        @forelse ($student->active_packages as $package)
                                            <div style="font-size:13px;">{{ $package->name }} <span class="text-muted">· sisa {{ $creditNum($package->remaining) }}</span></div>
                                        @empty
                                            <span class="text-muted small">Tidak ada paket aktif</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end">
                                        <span @class(['badge', 'badge-danger' => $balance <= 0, 'badge-warning' => $balance > 0 && $balance <= $lowCredit, 'badge-success' => $balance > $lowCredit])>{{ $creditNum($balance) }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">Rp {{ number_format($student->deposit_balance, 0, ',', '.') }}</td>
                                    <td class="text-nowrap small">{{ $student->last_activity ? \Illuminate\Support\Carbon::parse($student->last_activity)->translatedFormat('d M Y, H:i') : '-' }}</td>
                                    <td class="text-end"><a href="{{ route('course.student.show', $student->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        {{ array_filter($filters) ? 'Tidak ada murid yang cocok dengan filter.' : 'Belum ada murid yang punya paket InaYule.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $students->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>

@if ($canEdit)
    {{-- Import murid dari sistem lama: upload -> pratinjau (belum disimpan) -> konfirmasi. --}}
    <div class="modal fade" id="inayule-import" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('course.student.import.preview') }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Murid dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Kolom: <b>nama_murid</b>, <b>email</b>, no_hp, pengajar, paket, sisa_credit, sisa_saldo.
                        Nama paket & pengajar salin dari sheet di <a href="{{ route('course.student.import.template') }}">contoh Excel</a>.
                        Setelah upload, Anda melihat pratinjau dulu; data baru disimpan setelah dikonfirmasi.
                    </p>
                    <label class="form-label">File Excel</label>
                    <input type="file" name="file" accept=".xlsx,.xls" class="form-control mb-3" required>
                    <label class="form-label">Cabang murid baru</label>
                    <select name="branch_id" class="form-select">
                        <option value="">-- Tanpa cabang --</option>
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected(old('branch_id') === $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Hanya untuk murid yang belum ada di sistem. Murid lama (InaStudy/InaYule) tetap di cabangnya.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" onclick="this.disabled = true; this.form.submit();">Lihat Pratinjau</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
