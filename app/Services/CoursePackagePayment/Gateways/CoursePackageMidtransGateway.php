<?php

namespace App\Services\CoursePackagePayment\Gateways;

use App\Models\CoursePackagePayment;
use App\Models\PaymentGateway;
use App\Services\CoursePackagePayment\Contracts\CoursePackagePaymentGatewayInterface;
use App\Services\Payment\PaymentSignatureMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integrasi Midtrans Snap untuk checkout package. Logikanya sama persis
 * dengan App\Services\DepositPayment\Gateways\DepositMidtransGateway, cuma
 * dioperasikan di atas CoursePackagePayment & menagih `gateway_portion`
 * (bukan harga penuh) -- lihat docblock
 * CoursePackagePaymentGatewayInterface.
 *
 * Referensi resmi: https://docs.midtrans.com/docs/snap-snap-integration-guide
 */
class CoursePackageMidtransGateway implements CoursePackagePaymentGatewayInterface
{
    public function __construct(private PaymentGateway $config)
    {
    }

    private function isProduction(): bool
    {
        return $this->config->environment === 'production';
    }

    private function transactionUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function serverKey(): string
    {
        return (string) ($this->config->credentials['server_key'] ?? '');
    }

    public function requiresMethodSelection(): bool
    {
        return false;
    }

    public function getPaymentMethods(CoursePackagePayment $payment): array
    {
        return [];
    }

    public function createTransaction(CoursePackagePayment $payment, ?string $paymentMethod = null): array
    {
        [$firstName, $lastName] = $this->splitName((string) $payment->name);
        $amount = (int) round((float) $payment->gateway_portion);

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->transactionUrl(), [
                'transaction_details' => [
                    'order_id' => $payment->order_id,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $payment->email,
                    'phone' => $payment->handphone,
                ],
                'item_details' => [[
                    'id' => (string) $payment->course_package_id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Pembelian Package (sisa setelah saldo)',
                ]],
            ]);

        if ($response->failed()) {
            Log::error('[COURSE-PACKAGE][Midtrans] Gagal membuat transaksi', [
                'order_id' => $payment->order_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'Gagal membuat transaksi Midtrans: ' . ($response->json('error_messages.0') ?? $response->body())
            );
        }

        $data = $response->json();

        return [
            'redirect_url' => $data['redirect_url'] ?? null,
            'reference' => $data['token'] ?? null,
            'raw' => $data,
        ];
    }

    public function handleCallback(Request $request): array
    {
        $payload = $request->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        // Formula resmi: SHA512(order_id + status_code + gross_amount + server_key)
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey());

        if (!hash_equals($expected, $signatureKey)) {
            throw new PaymentSignatureMismatchException('Signature Midtrans tidak valid.');
        }

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        $isPaid = in_array($transactionStatus, ['capture', 'settlement'], true)
            && in_array($fraudStatus, [null, 'accept'], true);

        $isFailed = in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true);

        return [
            'order_id' => $orderId,
            'is_paid' => $isPaid,
            'is_failed' => $isFailed,
            'reference' => $payload['transaction_id'] ?? null,
            'raw' => $payload,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
