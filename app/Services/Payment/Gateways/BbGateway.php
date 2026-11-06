<?php

namespace App\Services\Payment\Gateways;

use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\Payable;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\PaymentSignatureMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Gateway "BB" (6 November 2026): pembayaran lewat API invoice BB, yang meneruskan ke Duitku.
 * Hosted checkout (tanpa langkah pilih metode), sama seperti iPaymu.
 *
 * Kredensial (Settings > Payment Gateway): base_url, api_key, secret_key, webhook_secret.
 * URL webhook yang didaftarkan di BB: {APP_URL}/api/payment/webhook/bb
 *
 * Keamanan:
 *  - request ke BB ditandatangani HMAC-SHA256 (timestamp, method, path, body) dengan secret_key;
 *  - webhook diverifikasi HMAC-SHA256(timestamp + "." + raw body) dengan webhook_secret, maks 5 menit;
 *  - status "lunas" dipastikan lagi lewat GET invoice ke BB (server ke server) sebelum dipercaya,
 *    dan nominalnya dikembalikan supaya dicocokkan dengan tagihan (lihat FormPaymentController::handleWebhook).
 */
class BbGateway implements PaymentGatewayInterface
{
    private const HEADER = 'X-Bizbos-';

    private const TOLERANCE_SECONDS = 300;

    public function __construct(private PaymentGateway $config)
    {
    }

    public function requiresMethodSelection(): bool
    {
        return false;
    }

    public function getPaymentMethods(Payable $payment): array
    {
        return [];
    }

    public function createTransaction(Payable $payment, ?string $paymentMethod = null): array
    {
        $data = $this->request('POST', '/api/v1/invoices', [
            'external_id' => $payment->getOrderId(),
            'amount' => (int) round($payment->getAmount()),
            'description' => mb_substr($payment->getDescription(), 0, 255),
            'customer' => array_filter([
                'name' => mb_substr($payment->getPayerName(), 0, 150),
                'email' => $payment->getPayerEmail() ?: null,
                'phone' => $payment->getPayerPhone() ?: null,
            ]),
            // Sama dengan batas waktu pembayaran di sisi kita (Settings > Payment Gateway).
            'expiry_minutes' => max(5, (int) ($this->config->expiry_minutes ?: 60)),
            'return_url' => $payment->getReturnUrl(),
        ]);

        return [
            // Langsung ke halaman bayar Duitku kalau sudah tersedia; kalau belum, halaman bayar BB.
            'redirect_url' => $data['payment_url'] ?? $data['checkout_url'] ?? null,
            'reference' => $data['id'] ?? null,
            'raw' => ['id' => $data['id'] ?? null, 'number' => $data['number'] ?? null, 'status' => $data['status'] ?? null],
        ];
    }

    public function handleCallback(Request $request): array
    {
        $raw = (string) $request->getContent();
        $timestamp = (string) $request->header(self::HEADER.'Timestamp');
        $signature = strtolower((string) $request->header(self::HEADER.'Signature'));
        $expected = hash_hmac('sha256', $timestamp.'.'.$raw, $this->credential('webhook_secret'));

        if ($signature === '' || ! ctype_digit($timestamp) || ! hash_equals($expected, $signature)
            || abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            throw new PaymentSignatureMismatchException('Signature webhook BB tidak valid atau kedaluwarsa.');
        }

        $event = json_decode($raw, true);
        $invoice = is_array($event['data'] ?? null) ? $event['data'] : [];
        $orderId = (string) ($invoice['external_id'] ?? '');
        $name = (string) ($event['event'] ?? '');
        $amount = null;
        $isPaid = false;

        // Lunas dipastikan lagi ke BB (bukan hanya dari isi webhook).
        if ($name === 'invoice.paid' && $orderId !== '') {
            $current = $this->request('GET', '/api/v1/invoices/'.rawurlencode($orderId));
            $isPaid = ($current['status'] ?? null) === 'paid' && ($current['external_id'] ?? null) === $orderId;
            $amount = isset($current['amount']) ? (int) $current['amount'] : null;
        }

        return [
            'order_id' => $orderId,
            'is_paid' => $isPaid,
            'is_failed' => in_array($name, ['invoice.expired', 'invoice.cancelled'], true),
            'reference' => $invoice['id'] ?? null,
            // Hanya ringkasan; data pembayar tidak ikut disimpan ulang.
            'raw' => ['event' => $name, 'id' => $invoice['id'] ?? null, 'status' => $invoice['status'] ?? null,
                'amount' => $invoice['amount'] ?? null, 'payment_method' => $invoice['payment_method'] ?? null],
            'amount' => $amount,
        ];
    }

    /** Request bertanda tangan ke API BB. @return array isi "data" dari respons */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        $base = rtrim($this->credential('base_url'), '/');

        if (! preg_match('#^https?://#i', $base)) {
            throw new RuntimeException('Base URL gateway BB belum benar.');
        }

        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();

        $response = Http::timeout(20)->acceptJson()->withHeaders([
            self::HEADER.'Key' => $this->credential('api_key'),
            self::HEADER.'Timestamp' => $timestamp,
            self::HEADER.'Signature' => hash_hmac('sha256', $timestamp."\n".$method."\n".$path."\n".$body, $this->credential('secret_key')),
        ])->withBody($body, 'application/json')->send($method, $base.$path);

        if ($response->failed() || ! $response->json('success')) {
            Log::error('[PAYMENT][BB] Request gagal', ['path' => $path, 'status' => $response->status(), 'message' => $response->json('message')]);

            throw new RuntimeException('Gagal menghubungi gateway BB: '.($response->json('message') ?? 'HTTP '.$response->status()));
        }

        return (array) $response->json('data');
    }

    private function credential(string $key): string
    {
        return (string) ($this->config->credentials[$key] ?? '');
    }
}
