@extends('layouts.frontend')
@section('content')

{{-- Halaman transit setelah user kembali dari gateway -- murni UX (polling
     status), TIDAK pernah jadi sumber kebenaran pembayaran. Lihat docblock
     InaYulePackageCheckoutController::return()/status() & style disamakan
     dengan resources/views/dashboard/deposit/return.blade.php. --}}

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">
            <h4 class="mb-3">Status Pembayaran</h4>

            @if (!$payment)
                <p class="text-muted">Transaksi tidak ditemukan.</p>
                <a href="{{ route('inayule.index') }}" class="btn btn-primary">Kembali</a>
            @else
                <p class="text-muted mb-2">{{ optional($payment->coursePackage)->name ?? 'Package' }}</p>
                <p id="cpp-status-text" class="mb-3">Memeriksa status pembayaran...</p>

                <a href="{{ route('inayule.index') }}" class="btn btn-primary">Kembali ke InaYule</a>

                <script>
                    (function () {
                        const statusUrl = @json(route('inayule.checkout.status', ['orderId' => $payment->order_id]));
                        const statusText = document.getElementById('cpp-status-text');

                        const labels = {
                            pending: 'Menunggu pembayaran... halaman ini akan diperbarui otomatis.',
                            paid: 'Pembayaran berhasil, credit sudah ditambahkan.',
                            failed: 'Pembayaran gagal atau dibatalkan.',
                            expired: 'Waktu pembayaran sudah habis.',
                        };

                        function renderStatus(status, creditBalance) {
                            statusText.textContent = labels[status] || status;

                            if (status === 'paid' && typeof creditBalance !== 'undefined') {
                                statusText.textContent += ' Sisa credit saat ini: ' + Number(creditBalance).toLocaleString('id-ID');
                            }
                        }

                        function poll() {
                            fetch(statusUrl, { headers: { Accept: 'application/json' } })
                                .then((res) => res.json())
                                .then((data) => {
                                    renderStatus(data.status, data.credit_balance);

                                    if (data.status === 'pending') {
                                        setTimeout(poll, 4000);
                                    }
                                })
                                .catch(() => setTimeout(poll, 6000));
                        }

                        poll();
                    })();
                </script>
            @endif
        </div>
    </div>
</div>
@endsection
