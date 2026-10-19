{{--
    Popup Snap Midtrans di halaman status pembayaran Inayule (19 Oktober 2026).
    Hanya untuk transaksi gateway 'midtrans' yang masih pending; transaksi Duitku
    tidak terpengaruh. Token Snap = gateway_reference, jadi refresh halaman tidak
    membuat transaksi baru. Status lunas tetap dari webhook, bukan dari popup ini.
--}}
@php
    $snapGateway = $payment->paymentGateway;
@endphp
@if ($payment->gateway === 'midtrans' && $payment->status === 'pending' && $payment->gateway_reference && $snapGateway)
    <div class="mb-3">
        <button type="button" class="btn btn-success" id="midtrans-pay-btn">Bayar Sekarang</button>
    </div>
    <script src="{{ $snapGateway->environment === 'production' ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ $snapGateway->credentials['client_key'] ?? '' }}"></script>
    <script>
        (function () {
            var token = @json($payment->gateway_reference);
            var btn = document.getElementById('midtrans-pay-btn');

            function openSnap() {
                if (!window.snap) return;
                window.snap.pay(token, {
                    onSuccess: function () { btn.classList.add('d-none'); },
                    onPending: function () {},
                    onError: function () {},
                    onClose: function () {}
                });
            }

            btn.addEventListener('click', openSnap);
            window.addEventListener('load', openSnap);
        })();
    </script>
@endif
