@extends('layouts.frontend')
@section('content')

{{--
    Honor Pengajar > Rekap Gabungan (25 November 2026): honor & jam mengajar tiap
    pengajar di setiap cabang dalam 1 bulan, lalu totalnya. Persetujuan & pembayaran
    tetap per cabang di halaman detail periode. Lihat TeacherHonorService::combinedRecap().
--}}

@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $hours = fn ($value) => \App\Models\ClassSession::hoursLabel((float) $value);
@endphp

<div class="middle-content container-xxl p-0">
    <p class="text-muted mb-3">Total honor setiap pengajar dari semua cabang dalam satu bulan (periode 26 &ndash; 25). Persetujuan dan pembayaran tetap dilakukan per cabang.</p>

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
                    <form method="GET" action="{{ route('teacher-honor.combined') }}" class="d-flex align-items-end gap-2">
                        <div>
                            <label class="form-label small text-muted mb-1">Bulan</label>
                            <select name="month" class="form-select" onchange="this.form.submit()">
                                @forelse ($months as $value)
                                    <option value="{{ $value }}" @selected($value === $month)>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $value)->translatedFormat('F Y') }}</option>
                                @empty
                                    <option value="">Belum ada periode</option>
                                @endforelse
                            </select>
                        </div>
                    </form>
                    <a href="{{ route('teacher-honor.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Honor Pengajar</a>
                </div>

                @if ($rows->isEmpty())
                    <p class="text-center text-muted py-4 mb-0">Belum ada kelas yang disetujui di bulan ini.</p>
                @else
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <p class="text-muted small mb-1">Pengajar</p>
                                <h5 class="mb-0">{{ $rows->count() }} orang</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <p class="text-muted small mb-1">Total Jam Mengajar</p>
                                <h5 class="mb-0">{{ $hours($rows->sum('total_hours')) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <p class="text-muted small mb-1">Total Honor Semua Cabang</p>
                                <h5 class="mb-0">{{ $rupiah($rows->sum('total_honor')) }}</h5>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Pengajar</th>
                                    @foreach ($branches as $branch)
                                        <th class="text-end">{{ $branch->name }}</th>
                                    @endforeach
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="fw-bold">{{ $row['teacher_name'] }}</td>
                                        @foreach ($branches as $branch)
                                            @php($cell = $row['branches'][$branch->id] ?? null)
                                            <td class="text-end text-nowrap">
                                                @if ($cell)
                                                    {{ $rupiah($cell['honor']) }}
                                                    <div class="small text-muted">{{ $hours($cell['hours']) }} &middot; {{ $cell['classes'] }} kelas</div>
                                                @else
                                                    <span class="text-muted">&ndash;</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-end text-nowrap">
                                            <strong>{{ $rupiah($row['total_honor']) }}</strong>
                                            <div class="small text-muted">{{ $hours($row['total_hours']) }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    @foreach ($branches as $branch)
                                        <td class="text-end text-nowrap">{{ $rupiah($rows->sum(fn ($row) => $row['branches'][$branch->id]['honor'] ?? 0)) }}</td>
                                    @endforeach
                                    <td class="text-end text-nowrap">{{ $rupiah($rows->sum('total_honor')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
