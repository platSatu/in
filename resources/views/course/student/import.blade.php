@extends('layouts.frontend')
@section('content')

{{-- Pratinjau import murid InaYule: belum ada data yang disimpan sampai tombol Simpan diklik. Lihat StudentImportService::plan(). --}}
@php
    $rows = collect($plan['rows']);
    $okRows = $rows->filter(fn ($row) => ! $row['errors'])->count();
    $errorRows = $rows->count() - $okRows;
@endphp

<div class="middle-content container-xxl p-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 layout-top-spacing mb-3">
        <div>
            <h5 class="mb-1">Pratinjau Import Murid</h5>
            <div class="text-muted small">{{ $fileName }} · Cabang murid baru: {{ $branch?->name ?? 'tanpa cabang' }}</div>
        </div>
        <a href="{{ route('course.student.index') }}" class="small">&larr; Batal, kembali ke Student InaYule</a>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([['Murid siap diimport', count($plan['students']), 'text-success'], ['Baris OK', $okRows, ''], ['Baris bermasalah (dilewati)', $errorRows, $errorRows ? 'text-danger' : '']] as [$label, $value, $class])
            <div class="col-md-4">
                <div class="widget-content widget-content-area br-8 h-100">
                    <div class="text-muted mb-1" style="font-size:13px;">{{ $label }}</div>
                    <div class="fw-bold {{ $class }}" style="font-size:26px;">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="widget-content widget-content-area br-8">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Baris</th><th>Nama / Email</th><th>Pengajar</th><th>Paket</th><th class="text-end">Sisa Credit</th><th class="text-end">Sisa Saldo</th><th>Hasil Pengecekan</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $line => $row)
                        <tr @class(['table-danger' => $row['errors']])>
                            <td>{{ $line }}</td>
                            <td>
                                <div class="fw-bold">{{ $row['nama_murid'] ?: '-' }}</div>
                                <div class="text-muted small">{{ $row['email'] ?: '-' }} {{ $row['no_hp'] ? '· ' . $row['no_hp'] : '' }}</div>
                            </td>
                            <td>{{ $row['pengajar'] ?: '-' }}</td>
                            <td>{{ $row['paket'] ?: '-' }}</td>
                            <td class="text-end">{{ $row['sisa_credit'] !== '' ? $row['sisa_credit'] : '-' }}</td>
                            <td class="text-end">{{ $row['sisa_saldo'] !== '' ? $row['sisa_saldo'] : '-' }}</td>
                            <td class="small">
                                @foreach ($row['errors'] as $error)<div class="text-danger">✕ {{ $error }}</div>@endforeach
                                @if (! $row['errors'])
                                    <div class="text-success">✓ Siap diimport</div>
                                    @foreach ($row['notes'] as $note)<div class="text-muted">{{ $note }}</div>@endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div class="small text-muted">
                Murid baru mendapat akun dengan email di atas dan cukup memakai <b>Lupa Password</b> di halaman login untuk membuat password.
                Baris bermasalah tidak ikut disimpan; perbaiki di Excel lalu upload ulang (yang sudah tersimpan otomatis dilewati).
            </div>
            @if ($plan['students'])
                <form method="POST" action="{{ route('course.student.import.store') }}" onsubmit="this.querySelector('button').disabled = true;">
                    @csrf
                    <button class="btn btn-primary">Simpan {{ count($plan['students']) }} Murid</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
