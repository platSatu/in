@extends('layouts.frontend')
@section('content')

{{-- Detail Student InaYule: profil & pengajar tetap, paket, riwayat credit, saldo, dan kelas. Lihat CourseStudentController::show(). --}}
@php
    $creditNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $canEdit = auth()->user()?->canAccessPermission('course.student', 'edit') ?? false;
    $sources = [
        'trial_claim' => 'Trial gratis', 'deposit_purchase' => 'Beli (saldo)', 'gateway_purchase' => 'Beli (online)',
        'mixed_purchase' => 'Beli (saldo + online)', 'upgrade_purchase' => 'Upgrade', 'convert_purchase' => 'Convert', 'import' => 'Import data lama',
    ];
    $sessionStatus = [
        'menunggu_guru' => ['Menunggu pengajar', 'badge-warning'], 'ditolak_guru' => ['Ditolak pengajar', 'badge-danger'],
        'menunggu_admin' => ['Menunggu admin', 'badge-warning'], 'disetujui' => ['Disetujui', 'badge-success'],
        'ditolak_admin' => ['Ditolak admin', 'badge-danger'], 'direfund' => ['Direfund', 'badge-info'], 'dibatalkan' => ['Dibatalkan', 'badge-secondary'],
    ];
@endphp

<div class="middle-content container-xxl p-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 layout-top-spacing mb-3">
        <a href="{{ route('course.student.index') }}" class="small">&larr; Kembali ke Student InaYule</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="widget-content widget-content-area br-8 h-100">
                <h5 class="mb-1">{{ trim($student->first_name . ' ' . $student->last_name) ?: '-' }}</h5>
                <div class="text-muted small">{{ $student->email ?: '-' }} · {{ $student->handphone ?: '-' }}</div>
                <div class="text-muted small mb-3">Cabang: {{ $student->companyBranch?->name ?? '-' }}</div>

                @if ($canEdit)
                    <form method="POST" action="{{ route('course.student.teacher', $student->id) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        @method('PUT')
                        <label class="small text-muted mb-0" for="teacher_user_id">Pengajar tetap</label>
                        <select name="teacher_user_id" id="teacher_user_id" class="form-select form-select-sm" style="max-width:240px;">
                            <option value="">-- Belum ditentukan --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($student->teacher_user_id === $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary">Simpan</button>
                    </form>
                    @error('teacher_user_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                @else
                    <div class="small">Pengajar tetap: <strong>{{ $student->teacher?->name ?? '-' }}</strong></div>
                @endif
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="widget-content widget-content-area br-8 h-100">
                <div class="text-muted mb-1" style="font-size:13px;">Sisa Credit</div>
                <div class="fw-bold" style="font-size:28px;">{{ $creditNum($creditBalance) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="widget-content widget-content-area br-8 h-100">
                <div class="text-muted mb-1" style="font-size:13px;">Sisa Saldo</div>
                <div class="fw-bold" style="font-size:24px;">Rp {{ number_format($depositBalance, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="widget-content widget-content-area br-8 mb-3">
        <h6 class="mb-3">Paket Dimiliki</h6>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Tanggal</th><th>Paket</th><th>Sumber</th><th class="text-end">Harga</th><th class="text-end">Credits</th><th class="text-end">Sisa</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td class="text-nowrap">{{ $purchase->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                            <td>{{ $purchase->coursePackage?->name ?? '-' }}</td>
                            <td>{{ $sources[$purchase->source] ?? $purchase->source }}</td>
                            <td class="text-end text-nowrap">Rp {{ number_format((float) $purchase->price_paid, 0, ',', '.') }}</td>
                            <td class="text-end">{{ $creditNum($purchase->credits_granted) }}</td>
                            <td class="text-end fw-bold">{{ $creditNum($purchase->credits_remaining) }}</td>
                            <td>
                                @if ($purchase->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($purchase->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada paket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="widget-content widget-content-area br-8 mb-3">
        <h6 class="mb-3">Riwayat Credit</h6>
        @include('course-credit._ledger', ['ledger' => $ledger])
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="widget-content widget-content-area br-8 h-100">
                <h6 class="mb-3">Riwayat Kelas</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Pengajar</th><th>Paket</th><th class="text-end">Credit</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                @php([$statusLabel, $statusClass] = $sessionStatus[$session->status] ?? [$session->status, 'badge-secondary'])
                                <tr>
                                    <td class="text-nowrap">{{ ($session->requested_at ?? $session->created_at)?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                    <td>{{ $session->teacher?->name ?? '-' }}</td>
                                    <td>{{ $session->coursePackage?->name ?? '-' }}</td>
                                    <td class="text-end">{{ $creditNum($session->credit_amount_final ?? $session->credit_amount_requested) }}</td>
                                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kelas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $sessions->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="widget-content widget-content-area br-8 h-100">
                <h6 class="mb-3">Riwayat Saldo</h6>
                @if ($deposits)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th></tr></thead>
                            <tbody>
                                @forelse ($deposits as $deposit)
                                    <tr>
                                        <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($deposit->payment_date ?? $deposit->created_at)->translatedFormat('d M Y, H:i') }}</td>
                                        <td>{{ $deposit->description ?: '-' }}</td>
                                        <td class="text-end text-success text-nowrap">{{ (float) $deposit->kredit > 0 ? '+Rp ' . number_format($deposit->kredit, 0, ',', '.') : '-' }}</td>
                                        <td class="text-end text-danger text-nowrap">{{ (float) $deposit->debit > 0 ? '−Rp ' . number_format($deposit->debit, 0, ',', '.') : '-' }}</td>
                                        <td class="text-end fw-bold text-nowrap">Rp {{ number_format((float) $deposit->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat saldo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $deposits->links('pagination::bootstrap-5') }}</div>
                @else
                    <p class="text-muted mb-0">Murid ini belum punya akun login, jadi belum punya saldo.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
