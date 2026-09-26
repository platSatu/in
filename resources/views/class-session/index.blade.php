@extends('layouts.frontend')
@section('content')

{{--
    FASE 2 bagian 2 "Absensi" -- sisi admin, gerbang FINAL sebelum credit
    benar-benar terpotong (lihat docblock ClassSessionAdminController).
    Admin bisa mengoreksi besaran credit lewat field "Koreksi Credit" saat
    approve -- kosongkan kalau tidak ada koreksi (dipakai apa adanya sesuai
    yang diajukan siswa & sudah disetujui pengajar).
--}}

<div class="middle-content container-xxl p-0">


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
                <h6 class="mb-3">Menunggu Approval Final (sudah disetujui pengajar)</h6>
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Diajukan</th>
                                <th>Siswa</th>
                                <th>Pengajar</th>
                                <th>Package</th>
                                <th>Credit Diajukan</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pending as $session)
                                <tr>
                                    <td>{{ optional($session->requested_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                    <td>{{ optional($session->teacher)->name ?? '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $session->id }}">Approve</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $session->id }}">Tolak</button>
                                        </div>

                                        <div class="modal fade" id="approveModal-{{ $session->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('class-session.approve', $session->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Approve Pengajuan</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p class="text-muted">Diajukan: {{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }} credit.</p>
                                                            <label class="form-label">Koreksi Credit (opsional)</label>
                                                            <input type="number" step="0.01" min="0.01" name="corrected_credit_amount" class="form-control" placeholder="Kosongkan kalau tidak ada koreksi">
                                                            <label class="form-label mt-2">Catatan (opsional)</label>
                                                            <textarea name="notes" class="form-control" rows="2"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-success">Approve & Potong Credit</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="rejectModal-{{ $session->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('class-session.reject', $session->id) }}">
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
                                    <td colspan="6" class="text-center text-muted">Tidak ada pengajuan yang menunggu approval final.</td>
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
                                <th>Pengajar</th>
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
                                    <td>{{ optional($session->teacher)->name ?? '-' }}</td>
                                    <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                    <td>{{ $session->credit_amount_final !== null ? number_format((float) $session->credit_amount_final, 2, ',', '.') : '-' }}</td>
                                    <td>
                                        @if ($session->status === 'disetujui')
                                            <span class="badge badge-success">Disetujui</span>
                                        @else
                                            <span class="badge badge-danger">Ditolak Admin</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada riwayat.</td>
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
