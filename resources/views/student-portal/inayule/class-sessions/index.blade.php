@extends('layouts.frontend')
@section('content')

{{--
    FASE 2 bagian 2 "Absensi" (16 September 2026) -- riwayat pengajuan
    pemakaian credit milik siswa yang sedang login. Status yang mungkin
    muncul (lihat App\Models\ClassSession): menunggu_guru, ditolak_guru,
    menunggu_admin, disetujui, ditolak_admin.
--}}

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">Riwayat Pengajuan Pemakaian Credit</h5>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('inayule.class-sessions.create') }}" class="btn btn-primary">+ Ajukan Pemakaian Credit</a>
            </div>
        </div>
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
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Diajukan</th>
                                <th>Pengajar</th>
                                <th>Package</th>
                                <th>Credit Diajukan</th>
                                <th>Credit Final</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr>
                                    <td>{{ optional($session->requested_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ optional($session->teacher)->name ?? '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}
                                        @if (optional(optional($session->coursePackage)->courseClass)->name)
                                            <div class="text-muted small">Kelas {{ $session->coursePackage->courseClass->name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                    <td>{{ $session->credit_amount_final !== null ? number_format((float) $session->credit_amount_final, 2, ',', '.') : '-' }}</td>
                                    <td>
                                        @php
                                            $statusLabel = [
                                                'menunggu_guru' => ['Menunggu Pengajar', 'badge-warning'],
                                                'ditolak_guru' => ['Ditolak Pengajar', 'badge-danger'],
                                                'menunggu_admin' => ['Menunggu Admin', 'badge-warning'],
                                                'disetujui' => ['Disetujui', 'badge-success'],
                                                'ditolak_admin' => ['Ditolak Admin', 'badge-danger'],
                                            ][$session->status] ?? [$session->status, 'badge-secondary'];
                                        @endphp
                                        <span class="badge {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                                    </td>
                                    <td>{{ $session->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada pengajuan pemakaian credit.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
