@extends('layouts.frontend')
@section('content')

{{--
    FASE 3 "Perhitungan Honor Pengajar" -- baris di sini SUDAH otomatis
    tercatat sejak admin approve pengajuan credit (Fase 2), lihat docblock
    TeacherHonorController. Halaman ini cuma laporan + 2 tombol transisi
    status (approve payout oleh Manager, tandai sudah dibayar) -- SISTEM
    TIDAK melakukan transfer uang apa pun.
--}}

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h5 class="mb-0">Honor Pengajar</h5>
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

    <div class="row layout-top-spacing">
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Belum Disetujui (Pending)</p>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['pending'], 0, ',', '.') }}</h5>
            </div>
        </div>
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Disetujui, Menunggu Dibayar</p>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['approved_for_payout'], 0, ',', '.') }}</h5>
            </div>
        </div>
        <div class="col-md-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <p class="text-muted mb-1">Sudah Dibayar</p>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['paid'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="mb-4">
                    <form method="GET" action="{{ route('teacher-honor.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Semua Status --</option>
                                <option value="pending" @selected($status === 'pending')>Pending</option>
                                <option value="approved_for_payout" @selected($status === 'approved_for_payout')>Disetujui, Menunggu Dibayar</option>
                                <option value="paid" @selected($status === 'paid')>Sudah Dibayar</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pengajar</th>
                                <th>Siswa</th>
                                <th>Package</th>
                                <th>Komisi</th>
                                <th>Nilai Credit</th>
                                <th>Honor</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($honors as $honor)
                                <tr>
                                    <td>{{ optional($honor->created_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ optional($honor->teacher)->name ?? '-' }}</td>
                                    <td>{{ $honor->student ? trim($honor->student->first_name . ' ' . $honor->student->last_name) : '-' }}</td>
                                    <td>{{ optional(optional($honor->classSession)->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ number_format((float) $honor->commission_percentage, 2, ',', '.') }}%</td>
                                    <td>Rp {{ number_format((float) $honor->credit_value, 0, ',', '.') }}</td>
                                    <td class="fw-bold">Rp {{ number_format((float) $honor->honor_amount, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $statusLabel = [
                                                'pending' => ['Pending', 'badge-warning'],
                                                'approved_for_payout' => ['Menunggu Dibayar', 'badge-info'],
                                                'paid' => ['Sudah Dibayar', 'badge-success'],
                                            ][$honor->status] ?? [$honor->status, 'badge-secondary'];
                                        @endphp
                                        <span class="badge {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($honor->status === 'pending')
                                            <form method="POST" action="{{ route('teacher-honor.approve-payout', $honor->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">Approve Payout</button>
                                            </form>
                                        @elseif ($honor->status === 'approved_for_payout')
                                            <form method="POST" action="{{ route('teacher-honor.mark-paid', $honor->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Tandai Dibayar</button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada data honor pengajar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $honors->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
