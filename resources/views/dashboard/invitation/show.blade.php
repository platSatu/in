@extends('layouts.frontend')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">👁️ Detail Undangan</h4>
            <small class="text-muted">{{ $invitation->name }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.invitation.edit', $invitation->id) }}"
               class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <a href="{{ route('dashboard.invitation.index') }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- Info --}}
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-user me-2 text-primary"></i> Informasi Tamu
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted fw-semibold">Nama</td>
                            <td>{{ $invitation->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">WhatsApp</td>
                            <td>{{ $invitation->handphone }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Universitas</td>
                            <td>{{ $invitation->university ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Program</td>
                            <td>{{ $invitation->program ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Jumlah Peserta</td>
                            <td>{{ $invitation->number_of_attendes ?? '-' }} orang</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Status</td>
                            <td>
                                @php
                                    $badge = match($invitation->status) {
                                        'hadir'       => ['success', 'Hadir'],
                                        'tidak_hadir' => ['danger',  'Tidak Hadir'],
                                        default       => ['warning', 'Pending'],
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge[0] }} px-3 py-2">{{ $badge[1] }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Kode QR</td>
                            <td><code class="fs-6">{{ $invitation->qrcode }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Didaftarkan</td>
                            <td>{{ $invitation->created_at->format('d M Y, H:i') }} WIB</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer d-flex gap-2">
                    {{-- Resend WA --}}
                    <form method="POST"
                          action="{{ route('dashboard.invitation.resend', $invitation->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Kirim ulang WhatsApp ke {{ $invitation->name }}?')">
                            <i class="fab fa-whatsapp me-1"></i> Kirim Ulang WA
                        </button>
                    </form>

                    {{-- Hapus --}}
                    <form method="POST"
                          action="{{ route('dashboard.invitation.destroy', $invitation->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"
                                onclick="return confirm('Hapus undangan ini?')">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="col-lg-5">
            <div class="card shadow-sm text-center h-100">
                <div class="card-header fw-semibold">
                    <i class="fas fa-qrcode me-2 text-primary"></i> QR Code
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                    @if($invitation->directory_qrcode && file_exists(public_path($invitation->directory_qrcode)))
                        <img src="{{ url($invitation->directory_qrcode) }}"
                             alt="QR Code {{ $invitation->name }}"
                             class="img-fluid"
                             style="max-width: 240px; border-radius: 12px;
                                    box-shadow: 0 4px 16px rgba(0,0,0,.12);">
                        <div class="mt-3">
                            <code class="fs-6 bg-light px-3 py-2 rounded">
                                {{ $invitation->qrcode }}
                            </code>
                        </div>
                        <a href="{{ url($invitation->directory_qrcode) }}"
                           download="qrcode-{{ $invitation->qrcode }}.png"
                           class="btn btn-outline-primary mt-3">
                            <i class="fas fa-download me-1"></i> Download QR Code
                        </a>
                    @else
                        <div class="text-muted py-5">
                            <i class="fas fa-qrcode fs-1 d-block mb-2 opacity-25"></i>
                            QR Code tidak tersedia
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
