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

    <div class="d-flex justify-content-end mb-2">
        <a href="{{ route('class-session.credit-history') }}" class="btn btn-sm btn-outline-secondary">Riwayat Credit Siswa</a>
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
                <h6 class="mb-1">Potong Credit Manual</h6>
                <p class="text-muted small mb-3">Untuk kelas yang dianggap hadir tanpa pengajuan siswa, mis. siswa tidak hadir lalu diganti video. Pilih pengajar kalau pengajar tetap mendapat honor; kosongkan kalau tidak.</p>
                {{-- Form dibuat lebar tetap; di layar sempit bisa digeser ke kanan supaya semua kolom & tombol terbaca. --}}
                <div class="overflow-auto pb-2">
                <form method="POST" action="{{ route('class-session.charge') }}" class="row g-2 align-items-end flex-nowrap" style="min-width: 1100px">
                    @csrf
                    <div class="col-4">
                        <label class="form-label">Siswa &amp; Paket</label>
                        <select name="student_package" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($chargeOptions as $option)
                                <option value="{{ $option['value'] }}" @selected(old('student_package') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-2">
                        <label class="form-label">Pengajar (opsional)</label>
                        <select name="teacher_user_id" class="form-select">
                            <option value="">Tanpa honor</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_user_id') === $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-1">
                        <label class="form-label">Credit</label>
                        <input type="number" name="credit_amount" class="form-control" min="0.5" max="10" step="0.5" value="{{ old('credit_amount', 1) }}" required>
                    </div>
                    <div class="col-2">
                        <label class="form-label">Tanggal Kelas</label>
                        <input type="datetime-local" name="class_at" class="form-control" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('class_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-2">
                        <label class="form-label">Alasan</label>
                        <input type="text" name="reason" class="form-control" maxlength="500" placeholder="Tidak hadir, diganti video" value="{{ old('reason') }}" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary text-nowrap px-4" onclick="return confirm('Potong credit siswa ini?')">Potong</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

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

                                        @include('class-session._reject-modal')
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

    @if ($waitingTeacher->isNotEmpty())
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="widget-content widget-content-area br-8">
                    <h6 class="mb-1">Menunggu Pengajar</h6>
                    <p class="text-muted small mb-3">Belum disetujui pengajar. Kalau pengajar tidak merespons, admin bisa menolaknya (credit belum terpotong) supaya periode Honor Pengajar bisa ditutup.</p>
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
                                @foreach ($waitingTeacher as $session)
                                    <tr>
                                        <td>{{ optional($session->requested_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                        <td>{{ optional($session->teacher)->name ?? '-' }}</td>
                                        <td>{{ optional($session->coursePackage)->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $session->id }}">Tolak</button>
                                            @include('class-session._reject-modal')
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                                <th class="no-content text-center">Action</th>
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
                                        @elseif ($session->status === 'direfund')
                                            <span class="badge badge-secondary">Direfund</span>
                                            <div class="small text-muted">{{ optional($session->refundedBy)->name ?? '-' }}, {{ optional($session->refunded_at)->format('d/m/Y H:i') }}: {{ $session->refund_reason }}</div>
                                        @else
                                            <span class="badge badge-danger">Ditolak Admin</span>
                                        @endif
                                        @if ($session->is_admin_entry)
                                            <div class="small text-muted">Dipotong admin: {{ $session->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($session->status === 'disetujui')
                                            <button type="button" class="btn btn-sm btn-outline-warning text-nowrap" data-bs-toggle="modal" data-bs-target="#refundModal-{{ $session->id }}">Refund</button>

                                            <div class="modal fade" id="refundModal-{{ $session->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('class-session.refund', $session->id) }}">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h6 class="modal-title">Refund Credit</h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <p class="text-muted">{{ number_format((float) $session->credit_amount_final, 2, ',', '.') }} credit akan dikembalikan ke paket siswa, dan sesi ini tidak lagi dihitung honor pengajar.</p>
                                                                <label class="form-label">Alasan refund</label>
                                                                <textarea name="reason" class="form-control" rows="2" maxlength="500" required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-warning">Refund Credit</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada riwayat.</td>
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
