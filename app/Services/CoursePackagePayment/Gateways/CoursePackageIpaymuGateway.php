<?php

namespace App\Services\CoursePackagePayment\Gateways;

use App\Models\CoursePackagePayment;
use App\Models\PaymentGateway;
use App\Services\CoursePackagePayment\Contracts\CoursePackagePaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integrasi iPaymu Public API v2 untuk checkout package. Logikanya sama
 * persis dengan App\Services\DepositPayment\Gateways\DepositIpaymuGateway,
 * cuma dioperasikan di atas CoursePackagePayment & menagih `gateway_portion`
 * (bukan harga penuh) -- lihat docblock
 * CoursePackagePaymentGatewayInterface.
 *
 * CATATAN KEAMANAN (sama seperti versi topup saldo & form_payments):
 * dokumentasi publik notifyUrl iPaymu v2 tidak menyebutkan field signature
 * untuk verifikasi callback, jadi status "paid" dari webhook iPaymu di bawah
 * ini tidak bisa diverifikasi seketat Midtrans/Duitku. Kalau iPaymu dipakai
 * di produksi untuk checkout package, sebaiknya dicocokkan juga dengan
 * mutasi/riwayat transaksi di dashboard iPaymu secara berkala.
 */
class CoursePackageIpaymuGateway implements CoursePackagePaymentGatewayInterface
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

    public function getPaymentMethods(CoursePackagePayment $payment): array
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

    public function createTransaction(CoursePackagePayment $payment, ?string $paymentMethod = null): array
    {
        $body = [
            'product' => ['Pembelian Package (sisa setelah saldo)'],
            'qty' => [1],
            'price' => [(int) round((float) $payment->gateway_portion)],
            'description' => ['Pembelian Package (sisa setelah saldo)'],
            'referenceId' => $payment->order_id,
            'buyerName' => $payment->name,
            'buyerEmail' => $payment->email,
            'buyerPhone' => $payment->handphone,
            'returnUrl' => route('inayule.checkout.return', ['order_id' => $payment->order_id]),
            'cancelUrl' => route('inayule.checkout.return', ['order_id' => $payment->order_id]),
            'notifyUrl' => route('course-package.webhook.ipaymu'),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'va' => $this->va(),
            'signature' => $this->buildSignature('POST', $body),
            'timestamp' => now()->format('YmdHis'),
        ])->post($this->paymentUrl(), $body);

        if ($response->failed()) {
            Log::error('[COURSE-PACKAGE][iPaymu] Gagal membuat transaksi', [
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
            'is_paid' => $statusCode === '1',
            'is_failed' => $statusCode === '2',
            'reference' => $request->input('trx_id'),
            'raw' => $request->all(),
        ];
    }
}
