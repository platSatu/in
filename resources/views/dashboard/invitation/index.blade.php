@extends('layouts.frontend')

@section('content')
<div class="container-fluid">

    {{-- Page title --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">🎫 Invitation Management</h4>
            <small class="text-muted">Manage invitation data & QR codes</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.invitation.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Invitation
            </a>

            <a href="{{ route('register-ulang.scan') }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i> Check-in
            </a>
        </div>
    </div>

    {{-- Alert --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted">Total</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success">{{ $stats['hadir'] }}</div>
                <small class="text-muted">Hadir</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger">{{ $stats['tidak_hadir'] }}</div>
                <small class="text-muted">Tidak Hadir</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning">{{ $stats['pending'] }}</div>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('dashboard.invitation.index') }}"
                  class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari nama, HP, universitas, QR code..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Status</option>
                        <option value="hadir"       {{ request('status') === 'hadir'       ? 'selected' : '' }}>Hadir</option>
                        <option value="tidak_hadir" {{ request('status') === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('dashboard.invitation.index') }}" class="btn btn-outline-secondary flex-fill">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Daftar Undangan</span>
            <span class="badge bg-secondary">{{ $invitations->total() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40">No</th>
                        <th>Nama</th>
                        <th>WhatsApp</th>
                        <th>University</th>
                        <th>Program</th>
                        <th class="text-center">Peserta</th>
                        <th class="text-center">Qrcode</th>
                        <th class="text-center">Status</th>
                        <!-- <th class="text-center">QR Code</th> -->
                        <th class="text-center" width="180">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invitations as $i => $inv)
                    <tr>
                        <td class="text-muted">{{ $invitations->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $inv->name }}</div>
                            <small class="text-muted">{{ $inv->created_at->format('d M Y H:i') }}</small>
                        </td>
                        <td>{{ $inv->handphone }}</td>
                        <td>{{ $inv->university ?? '-' }}</td>
                        <td>{{ $inv->program ?? '-' }}</td>
                        <td class="text-center">{{ $inv->number_of_attendes ?? '-' }}</td>
                        <td>{{ $inv->qrcode ?? '-' }}</td>
                        <td class="text-center">
                            @php
                                $badge = match($inv->status) {
                                    'hadir'       => ['success', 'Hadir'],
                                    'tidak_hadir' => ['danger',  'Tidak Hadir'],
                                    
                                };
                            @endphp
                            <span class="badge bg-{{ $badge[0] }}">{{ $badge[1] }}</span><br>
                            <small class="text-muted">{{ $inv->checked_in_at ? $inv->checked_in_at->format('d M Y H:i') : '-' }}</small>
                        </td>
                        <!-- <td class="text-center">
                            @if($inv->directory_qrcode && file_exists(public_path($inv->directory_qrcode)))
                                <img src="{{ url($inv->directory_qrcode) }}"
                                     alt="QR" width="48" height="48"
                                     style="border-radius:6px; cursor:pointer;"
                                     data-bs-toggle="tooltip"
                                     title="{{ $inv->qrcode }}">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td> -->
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('invitation.show', $inv->qrcode) }}"
                                   class="btn btn-sm btn-outline-info" title="Detail" target="_blank">
                                    Detail
                                </a>
                                <a href="{{ route('dashboard.invitation.edit', $inv->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    Edit
                                </a>
                                <form method="POST"
                                      action="{{ route('dashboard.invitation.resend', $inv->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success"
                                            title="Kirim Ulang WA"
                                            onclick="return confirm('Kirim ulang WA ke {{ $inv->name }}?')">
                                        Resend
                                    </button>
                                </form>
                                <form method="POST"
                                      action="{{ route('dashboard.invitation.destroy', $inv->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Hapus"
                                            onclick="return confirm('Hapus undangan {{ $inv->name }}?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fs-1 d-block mb-2"></i>
                            No Data Available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invitations->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $invitations->firstItem() }}–{{ $invitations->lastItem() }}
                    dari {{ $invitations->total() }} data
                </small>
                {{ $invitations->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

</div>
<script>
    // Tooltip Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el));
</script>
@endsection

@push('scripts')

@endpush
