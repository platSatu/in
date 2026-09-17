@extends('layouts.frontend')
@section('content')

{{--
    FASE 2 bagian 2 "Absensi" -- sisi pengajar. Approve PERSIS saat kelas
    dimulai (lihat docblock ClassSessionWorkflowService), belum memotong
    credit sama sekali -- baru diteruskan ke antrian admin.
--}}

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h5 class="mb-0">Approval Pemakaian Credit -- Pengajar</h5>
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

    {{--
        FASE 2 bagian 3 "Jadwal" (16 September 2026) -- "hari ini saya
        ngajar siapa saja", lihat docblock ClassSessionApprovalController.
        Ditampilkan apa adanya termasuk status (bisa saja masih menunggu
        approval siapa pun), TIDAK menyaring status tertentu -- tujuannya
        murni "siapa yang terjadwal hari ini", bukan "siapa yang sudah
        pasti approved".
    --}}
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-3">Jadwal Hari Ini</h6>
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Siswa</th>
                                <th>Package</th>
                                <th>Credit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($today as $session)
                                <tr>
                                    <td>{{ optional($session->requested_at)->format('H:i') }}</td>
                                    <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $todayStatusLabel = [
                                                'menunggu_guru' => ['Menunggu Anda', 'badge-warning'],
                                                'ditolak_guru' => ['Ditolak Anda', 'badge-danger'],
                                                'menunggu_admin' => ['Menunggu Admin', 'badge-warning'],
                                                'disetujui' => ['Disetujui', 'badge-success'],
                                                'ditolak_admin' => ['Ditolak Admin', 'badge-danger'],
                                            ][$session->status] ?? [$session->status, 'badge-secondary'];
                                        @endphp
                                        <span class="badge {{ $todayStatusLabel[1] }}">{{ $todayStatusLabel[0] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Tidak ada jadwal hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-3">Menunggu Approval Anda</h6>
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Diajukan</th>
                                <th>Siswa</th>
                                <th>Package</th>
                                <th>Credit Diajukan</th>
                                <th>Catatan</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pending as $session)
                                <tr>
                                    <td>{{ optional($session->requested_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                    <td>{{ $session->notes ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <form method="POST" action="{{ route('teacher.class-sessions.approve', $session->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success text-nowrap">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $session->id }}">Tolak</button>
                                        </div>

                                        <div class="modal fade" id="rejectModal-{{ $session->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('teacher.class-sessions.reject', $session->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Tolak Pengajuan</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label class="form-label">Alasan (opsional)</label>
                                                            <textarea name="reason" class="form-control" rows="2"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada pengajuan yang menunggu approval Anda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-3">Riwayat</h6>
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Diajukan</th>
                                <th>Siswa</th>
                                <th>Package</th>
                                <th>Credit Final</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $session)
                                <tr>
                                    <td>{{ optional($session->requested_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ $session->credit_amount_final !== null ? number_format((float) $session->credit_amount_final, 2, ',', '.') : '-' }}</td>
                                    <td>
                                        @php
                                            $statusLabel = [
                                                'menunggu_admin' => ['Menunggu Admin', 'badge-warning'],
                                                'disetujui' => ['Disetujui', 'badge-success'],
                                                'ditolak_admin' => ['Ditolak Admin', 'badge-danger'],
                                                'ditolak_guru' => ['Ditolak Saya', 'badge-danger'],
                                            ][$session->status] ?? [$session->status, 'badge-secondary'];
                                        @endphp
                                        <span class="badge {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada riwayat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $history->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
