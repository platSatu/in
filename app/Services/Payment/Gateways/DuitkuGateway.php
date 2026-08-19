<?php

namespace App\Services\Payment\Gateways;

use App\Models\FormPayment;
use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\PaymentSignatureMismatchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Integrasi Duitku API v2. Berbeda dari Midtrans/iPaymu, endpoint "Request
 * Transaction" (v2/inquiry) milik Duitku WAJIB diberi paymentMethod spesifik
 * (VA, QRIS, e-wallet, dll) — tidak ada halaman pilih-metode bawaan seperti
 * Snap. Makanya alurnya 2 langkah: getPaymentMethods() dulu untuk ditampilkan
 * sebagai pilihan di wizard kita, baru createTransaction() dipanggil lagi
 * setelah user memilih salah satu.
 *
 * Referensi resmi: https://docs.duitku.com/api/en/
 */
class DuitkuGateway implements PaymentGatewayInterface
{
    public function __construct(private PaymentGateway $config)
    {
    }

    private function isProduction(): bool
    {
        return $this->config->environment === 'production';
    }

    private function paymentMethodUrl(): string
    {
        return $this->isProduction()
            ? 'https://passport.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod'
            : 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
    }

    private function inquiryUrl(): string
    {
        return $this->isProduction()
            ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry'
            : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';
    }

    private function merchantCode(): string
    {
        return (string) ($this->config->credentials['merchant_code'] ?? '');
    }

    private function apiKey(): string
    {
        return (string) ($this->config->credentials['secret_key'] ?? '');
    }

    public function requiresMethodSelection(): bool
    {
        return true;
    }

    public function getPaymentMethods(FormPayment $payment): array
    {
        $amount = (int) round((float) $payment->amount);
        $datetime = now()->format('Y-m-d H:i:s');

        // Formula resmi: SHA256(merchantcode + amount + datetime + apiKey)
        $signature = hash('sha256', $this->merchantCode() . $amount . $datetime . $this->apiKey());

        $response = Http::acceptJson()->post($this->paymentMethodUrl(), [
            'merchantcode' => $this->merchantCode(),
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]);

        if ($response->failed()) {
            Log::error('[PAYMENT][Duitku] Gagal ambil daftar metode pembayaran', [
                'order_id' => $payment->order_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Gagal mengambil daftar metode pembayaran Duitku.');
        }

        return collect($response->json('paymentFee', []))
            ->map(fn (array $item) => [
                'code' => $item['paymentMethod'] ?? '',
                'name' => $item['paymentName'] ?? ($item['paymentMethod'] ?? ''),
                'image' => $item['paymentImage'] ?? null,
                'fee' => $item['totalFee'] ?? 0,
            ])
            ->filter(fn (array $item) => $item['code'] !== '')
            ->values()
            ->all();
    }

    public function createTransaction(FormPayment $payment, ?string $paymentMethod = null): array
    {
        if (empty($paymentMethod)) {
            throw new InvalidArgumentException('Duitku butuh paymentMethod yang sudah dipilih user sebelum transaksi dibuat.');
        }

        $amount = (int) round((float) $payment->amount);

        // Formula resmi: MD5(merchantCode + merchantOrderId + paymentAmount + apiKey)
        $signature = md5($this->merchantCode() . $payment->order_id . $amount . $this->apiKey());

        $response = Http::acceptJson()->post($this->inquiryUrl(), [
            'merchantCode' => $this->merchantCode(),
            'paymentAmount' => $amount,
            'merchantOrderId' => $payment->order_id,
            'productDetails' => 'Pembayaran ' . ($payment->form->name ?? 'Form'),
            'email' => $payment->email,
            'paymentMethod' => $paymentMethod,
            'customerVaName' => $payment->name,
            'returnUrl' => route('frontend.payment.return', ['order_id' => $payment->order_id]),
            'callbackUrl' => route('payment.webhook.duitku'),
            'signature' => $signature,
            'expiryPeriod' => 60,
        ]);

        if ($response->failed()) {
            Log::error('[PAYMENT][Duitku] Gagal membuat transaksi', [
                'order_id' => $payment->order_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'Gagal membuat transaksi Duitku: ' . ($response->json('statusMessage') ?? $response->body())
            );
        }

        $data = $response->json();

        return [
            'redirect_url' => $data['paymentUrl'] ?? null,
            'reference' => $data['reference'] ?? null,
            'raw' => $data,
        ];
    }

    public function handleCallback(Request $request): array
    {
        $merchantCode = (string) $request->input('merchantCode');
        $amount = (string) $request->input('amount');
        $merchantOrderId = (string) $request->input('merchantOrderId');
        $resultCode = (string) $request->input('resultCode');
        $signature = (string) $request->input('signature');

        // Formula resmi: MD5(merchantcode + amount + merchantOrderId + apiKey)
        $expected = md5($merchantCode . $amount . $merchantOrderId . $this->apiKey());

        if (!hash_equals($expected, $signature)) {
            throw new PaymentSignatureMismatchException('Signature Duitku tidak valid.');
        }

        return [
            'order_id' => $merchantOrderId,
            // resultCode "00" = sukses/dibayar, "01" = gagal.
            'is_paid' => $resultCode === '00',
            'is_failed' => $resultCode !== '00',
            'reference' => $request->input('reference'),
            'raw' => $request->all(),
        ];
    }
}
