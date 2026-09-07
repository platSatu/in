@extends('layouts.frontend')

@section('content')
    <div class="col-lg-6 mx-auto layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area text-center">
                <h4 class="mb-3">Status Pembayaran</h4>

                @if (!$payment)
                    <p class="text-muted">Transaksi tidak ditemukan.</p>
                    <a href="{{ route('dashboard.deposit.create') }}" class="btn btn-primary">Kembali</a>
                @else
                    <p id="deposit-status-text" class="mb-3">Memeriksa status pembayaran...</p>

                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Kembali ke Dashboard</a>

                    <script>
                        (function () {
                            const orderId = @json($payment->order_id);
                            const statusUrl = @json(route('dashboard.deposit.status', ['order_id' => $payment->order_id]));
                            const statusText = document.getElementById('deposit-status-text');

                            const labels = {
                                pending: 'Menunggu pembayaran... halaman ini akan diperbarui otomatis.',
                                paid: 'Pembayaran berhasil, saldo sudah ditambahkan.',
                                failed: 'Pembayaran gagal atau dibatalkan.',
                                expired: 'Waktu pembayaran sudah habis.',
                            };

                            function renderStatus(status, balance) {
                                statusText.textContent = labels[status] || status;

                                if (status === 'paid' && typeof balance !== 'undefined') {
                                    statusText.textContent += ' Saldo saat ini: Rp ' + Number(balance).toLocaleString('id-ID');
                                }
                            }

                            function poll() {
                                fetch(statusUrl, { headers: { Accept: 'application/json' } })
                                    .then((res) => res.json())
                                    .then((data) => {
                                        renderStatus(data.status, data.balance);

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
