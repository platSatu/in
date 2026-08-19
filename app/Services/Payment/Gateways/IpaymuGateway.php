<?php

namespace App\Services\Payment\Gateways;

use App\Models\FormPayment;
use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integrasi iPaymu Public API v2 (hosted checkout via "redirect payment" —
 * user diarahkan ke halaman iPaymu untuk pilih channel pembayaran sendiri,
 * jadi tidak butuh langkah pilih-metode seperti Duitku).
 *
 * Referensi: https://ipaymu.com/api-collection/ (Public API v2)
 *
 * CATATAN KEAMANAN PENTING: berbeda dari Midtrans (SHA-512) dan Duitku (MD5),
 * dokumentasi publik notifyUrl iPaymu v2 yang tersedia saat ini TIDAK
 * menyebutkan field signature untuk verifikasi callback. Karena itu status
 * "paid" dari webhook iPaymu di bawah ini TIDAK bisa diverifikasi seketat
 * dua gateway lain. Kalau iPaymu dipakai di production, sebaiknya cocokkan
 * juga dengan mutasi/riwayat transaksi di dashboard iPaymu secara berkala.
 */
class IpaymuGateway implements PaymentGatewayInterface
{
    public function __construct(private PaymentGateway $config)
    {
    }

    private function isProduction(): bool
    {
        return $this->config->environment === 'production';
    }

    private function paymentUrl(): string
    {
        return $this->isProduction()
            ? 'https://my.ipaymu.com/api/v2/payment'
            : 'https://sandbox.ipaymu.com/api/v2/payment';
    }

    private function va(): string
    {
        return (string) ($this->config->credentials['va'] ?? '');
    }

    private function apiKey(): string
    {
        return (string) ($this->config->credentials['api_key'] ?? '');
    }

    public function requiresMethodSelection(): bool
    {
        return false;
    }

    public function getPaymentMethods(FormPayment $payment): array
    {
        return [];
    }

    /**
     * Formula resmi: HMAC-SHA256("{METHOD}:{VA}:{sha256(body)}:{apiKey}", apiKey).
     */
    private function buildSignature(string $method, array $body): string
    {
        $bodyJson = empty($body) ? '{}' : json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash = strtolower(hash('sha256', $bodyJson));
        $stringToSign = strtoupper($method) . ':' . $this->va() . ':' . $bodyHash . ':' . $this->apiKey();

        return hash_hmac('sha256', $stringToSign, $this->apiKey());
    }

    public function createTransaction(FormPayment $payment, ?string $paymentMethod = null): array
    {
        $body = [
            'product' => [$payment->form->name ?? 'Pembayaran Form'],
            'qty' => [1],
            'price' => [(int) round((float) $payment->amount)],
            'description' => ['Pembayaran ' . ($payment->form->name ?? 'Form')],
            'referenceId' => $payment->order_id,
            'buyerName' => $payment->name,
            'buyerEmail' => $payment->email,
            'buyerPhone' => $payment->handphone,
            'returnUrl' => route('frontend.payment.return', ['order_id' => $payment->order_id]),
            'cancelUrl' => route('frontend.payment.return', ['order_id' => $payment->order_id]),
            'notifyUrl' => route('payment.webhook.ipaymu'),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'va' => $this->va(),
            'signature' => $this->buildSignature('POST', $body),
            'timestamp' => now()->format('YmdHis'),
        ])->post($this->paymentUrl(), $body);

        if ($response->failed()) {
            Log::error('[PAYMENT][iPaymu] Gagal membuat transaksi', [
                'order_id' => $payment->order_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'Gagal membuat transaksi iPaymu: ' . ($response->json('Message') ?? $response->body())
            );
        }

        $data = $response->json();

        return [
            'redirect_url' => $data['Data']['Url'] ?? null,
            'reference' => $data['Data']['SessionID'] ?? null,
            'raw' => $data,
        ];
    }

    public function handleCallback(Request $request): array
    {
        $orderId = (string) $request->input('reference_id');
        $statusCode = (string) $request->input('status_code');

        return [
            'order_id' => $orderId,
            // status_code dari iPaymu: 0 = pending, 1 = berhasil, 2 = gagal.
            'is_paid' => $statusCode === '1',
            'is_failed' => $statusCode === '2',
            'reference' => $request->input('trx_id'),
            'raw' => $request->all(),
        ];
    }
}
