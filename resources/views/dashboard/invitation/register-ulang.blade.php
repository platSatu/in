@extends('layouts.frontend')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">📷 Registrasi Ulang</h4>
            <small class="text-muted">Scan QR code untuk konfirmasi kehadiran</small>
        </div>
        <a href="{{ route('dashboard.invitation.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Stats realtime --}}
    <div class="row g-3 mb-4" id="stats-row">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-primary" id="stat-total">{{ $stats['total'] }}</div>
                <small class="text-muted">Total Peserta</small>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success" id="stat-hadir">{{ $stats['hadir'] }}</div>
                <small class="text-muted">Sudah Hadir</small>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-danger" id="stat-belum">{{ $stats['belum'] }}</div>
                <small class="text-muted">Belum Hadir</small>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    {{-- Input QR (fokus otomatis untuk scanner) --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-qrcode me-1 text-primary"></i>
                            Scan atau Input QR Code
                        </label>
                        <input
                            type="text"
                            id="qrcode-input"
                            class="form-control form-control-lg"
                            placeholder="Arahkan scanner ke QR code..."
                            autocomplete="off"
                            autofocus
                            style="font-size: 1.3rem; letter-spacing: 2px;"
                        >
                        <small class="text-muted">
                            Input otomatis diproses saat scanner selesai membaca
                        </small>
                    </div>

                    {{-- Result area --}}
                    <div id="result-area" class="d-none">
                        <div id="result-box"
                             class="rounded-3 p-4 text-center"
                             style="transition: all .3s ease;">

                            {{-- Icon status --}}
                            <div id="result-icon" class="mb-3" style="font-size: 3.5rem;"></div>

                            {{-- Pesan utama --}}
                            <h4 id="result-message" class="fw-bold mb-3"></h4>

                            {{-- Detail peserta --}}
                            <div id="result-detail"
                                 class="text-start rounded-3 p-3 d-none"
                                 style="background: rgba(0,0,0,.05);">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="fw-semibold" width="40%">Nama</td>
                                        <td id="d-name">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Universitas</td>
                                        <td id="d-university">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Program</td>
                                        <td id="d-program">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Peserta</td>
                                        <td id="d-attendees">-</td>
                                    </tr>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
<script>
(function () {
    const input      = document.getElementById('qrcode-input');
    const resultArea = document.getElementById('result-area');
    const resultBox  = document.getElementById('result-box');
    const resultIcon = document.getElementById('result-icon');
    const resultMsg  = document.getElementById('result-message');
    const detail     = document.getElementById('result-detail');

    // Stat counters
    let statHadir = parseInt(document.getElementById('stat-hadir').textContent);
    let statBelum = parseInt(document.getElementById('stat-belum').textContent);

    // Pastikan input selalu fokus (untuk scanner fisik)
    document.addEventListener('click', () => input.focus());
    input.focus();

    let debounceTimer = null;

    // Scanner fisik kirim Enter setelah scan
    // Debounce 300ms untuk handle scanner yang cepat
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            processQrcode();
        }
    });

    // Auto-submit setelah tidak ada input selama 500ms
    // (beberapa scanner tidak kirim Enter)
    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        if (this.value.trim().length >= 5) {
            debounceTimer = setTimeout(() => processQrcode(), 500);
        }
    });

    function processQrcode() {
        const qrcode = input.value.trim();
        if (!qrcode) return;

        // Disable input sementara
        input.disabled = true;
        showLoading();

        fetch('{{ route("register-ulang.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type'  : 'application/json',
                'X-CSRF-TOKEN'  : '{{ csrf_token() }}',
                'Accept'        : 'application/json',
            },
            body: JSON.stringify({ qrcode }),
        })
        .then(res => res.json())
        .then(data => {
            switch (data.status) {
                case 'success':
                    showSuccess(data.message, data.data);
                    updateStats('hadir');
                    break;
                case 'already':
                    showWarning(data.message, data.data);
                    break;
                case 'not_found':
                    showError(data.message);
                    break;
                default:
                    showError('Terjadi kesalahan.');
            }
        })
        .catch(() => showError('Gagal menghubungi server.'))
        .finally(() => {
            // Reset input & fokus lagi setelah 3 detik
            setTimeout(() => {
                input.value    = '';
                input.disabled = false;
                input.focus();
                hideResult();
            }, 3000);
        });
    }

    // ── Tampilan status ──────────────────────────────────────

    function showLoading() {
        resultArea.classList.remove('d-none');
        resultBox.style.background = '#f8f9fa';
        resultBox.style.color      = '#333';
        resultIcon.textContent     = '⏳';
        resultMsg.textContent      = 'Memproses...';
        detail.classList.add('d-none');
    }

    function showSuccess(message, data) {
        resultBox.style.background = '#d1fae5';
        resultBox.style.color      = '#065f46';
        resultIcon.textContent     = '✅';
        resultMsg.textContent      = message;
        fillDetail(data);
    }

    function showWarning(message, data) {
        resultBox.style.background = '#fef3c7';
        resultBox.style.color      = '#92400e';
        resultIcon.textContent     = '⚠️';
        resultMsg.textContent      = message;
        fillDetail(data);
    }

    function showError(message) {
        resultBox.style.background = '#fee2e2';
        resultBox.style.color      = '#991b1b';
        resultIcon.textContent     = '❌';
        resultMsg.textContent      = message;
        detail.classList.add('d-none');
    }

    function hideResult() {
        resultArea.classList.add('d-none');
        detail.classList.add('d-none');
    }

    function fillDetail(data) {
        if (!data) { detail.classList.add('d-none'); return; }
        document.getElementById('d-name').textContent       = data.name        ?? '-';
        document.getElementById('d-university').textContent = data.university   ?? '-';
        document.getElementById('d-program').textContent    = data.program      ?? '-';
        document.getElementById('d-attendees').textContent  = data.number_of_attendes
            ? data.number_of_attendes + ' orang' : '-';
        detail.classList.remove('d-none');
    }

    // ── Update stat counter tanpa reload ────────────────────
    function updateStats(type) {
        if (type === 'hadir') {
            statHadir++;
            statBelum = Math.max(0, statBelum - 1);
            document.getElementById('stat-hadir').textContent = statHadir;
            document.getElementById('stat-belum').textContent = statBelum;
        }
    }

})();
</script>
@endsection

@push('scripts')

@endpush