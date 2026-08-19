<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormPayment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentSignatureMismatchException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Controller PUBLIK (tanpa login) yang menjembatani wizard form
 * (resources/views/frontend/form-wizard.blade.php) dengan gateway
 * pembayaran yang admin pilih di Settings > Payment Gateway.
 *
 * Alur singkat:
 *   1. init()               - dipanggil setelah step "Nama/Email/HP" diisi.
 *   2. selectDuitkuMethod() - khusus Duitku, setelah user pilih metode.
 *   3. status()             - dipoll wizard untuk tahu kapan sudah dibayar.
 *   4. return()             - halaman transit setelah user kembali dari gateway.
 *   5. *Webhook()           - dipanggil SERVER gateway (bukan browser user),
 *                              satu-satunya tempat status transaksi boleh
 *                              berubah jadi "paid".
 */
class FormPaymentController extends Controller
{
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form_id' => ['required', 'string', 'exists:forms,id'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.\'-]+$/u'],
            'email' => ['required', 'email', 'max:255'],
            'handphone' => ['required', 'digits_between:9,16'],
        ]);

        $form = Form::findOrFail($validated['form_id']);

        if (!$form->requires_payment) {
            return response()->json(['message' => 'Form ini tidak membutuhkan pembayaran.'], 422);
        }

        $gateway = PaymentGateway::where('user_id', $form->user_id)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (!$gateway) {
            return response()->json([
                'message' => 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.',
            ], 422);
        }

        // === TIMER PEMBAYARAN ===
        // expires_at dihitung sekali di sini dari expiry_minutes milik gateway yang
        // sedang aktif (Settings > Payment Gateway, default 60 menit) — dipakai untuk
        // countdown di wizard (lihat form-wizard.blade.php) dan penanda "expired" di
        // status() di bawah, supaya berlaku SAMA untuk semua gateway (Duitku/Midtrans/
        // iPaymu), bukan cuma Duitku.
        $payment = FormPayment::create([
            'form_id' => $form->id,
            'payment_gateway_id' => $gateway->id,
            'order_id' => $this->generateOrderId(),
            'gateway' => $gateway->gateway,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'handphone' => $validated['handphone'],
            'amount' => $form->payment_amount ?? 0,
            'status' => 'pending',
            'expires_at' => now()->addMinutes($gateway->expiry_minutes ?? 60),
        ]);

        try {
            $driver = PaymentGatewayFactory::make($gateway);

            if ($driver->requiresMethodSelection()) {
                return response()->json([
                    'mode' => 'select-method',
                    'order_id' => $payment->order_id,
                    'methods' => $driver->getPaymentMethods($payment),
                    'expires_at' => optional($payment->expires_at)->toIso8601String(),
                    'server_time' => now()->toIso8601String(),
                ]);
            }

            $result = $driver->createTransaction($payment);

            $payment->update([
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            return response()->json([
                'mode' => 'redirect',
                'order_id' => $payment->order_id,
                'redirect_url' => $result['redirect_url'] ?? null,
                'expires_at' => optional($payment->expires_at)->toIso8601String(),
                'server_time' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Log::error('[PAYMENT] Gagal init transaksi', [
                'order_id' => $payment->order_id,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran, silakan coba lagi.'], 500);
        }
    }

    public function selectDuitkuMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'exists:form_payments,order_id'],
            'payment_method' => ['required', 'string', 'max:10'],
        ]);

        $payment = FormPayment::where('order_id', $validated['order_id'])
            ->where('status', 'pending')
            ->firstOrFail();

        $gateway = $payment->paymentGateway;

        if (!$gateway || $gateway->gateway !== 'duitku') {
            return response()->json(['message' => 'Transaksi tidak valid.'], 422);
        }

        try {
            $driver = PaymentGatewayFactory::make($gateway);
            $result = $driver->createTransaction($payment, $validated['payment_method']);

            $payment->update([
                'payment_method' => $validated['payment_method'],
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            return response()->json(['redirect_url' => $result['redirect_url'] ?? null]);
        } catch (Throwable $e) {
            Log::error('[PAYMENT][Duitku] Gagal buat transaksi setelah pilih metode', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran, silakan coba lagi.'], 500);
        }
    }

    /**
     * Dipoll oleh wizard tiap beberapa detik. Ini yang jadi dasar "placement
     * test baru muncul setelah callback pembayaran diterima" — statusnya
     * hanya berubah jadi "paid" lewat webhook, bukan dari halaman ini.
     *
     * === TIMER PEMBAYARAN — SELF-HEAL EXPIRED ===
     * Setiap kali di-poll, transaksi yang masih "pending" tapi sudah lewat
     * expires_at langsung ditandai "expired" di sini juga (tidak perlu nunggu
     * scheduled command payments:expire-stale) — supaya wizard yang masih
     * terbuka langsung dapat status expired di polling berikutnya, biar sinkron
     * dengan dashboard gateway aslinya (mis. Duitku yang punya expiryPeriod sendiri).
     *
     * UPDATE ber-syarat (WHERE status='pending') dipakai, BUKAN load-then-save,
     * supaya atomik & race-safe terhadap webhook gateway yang mungkin baru saja
     * menandai baris yang sama "paid" di saat hampir bersamaan — kalau itu terjadi,
     * WHERE di bawah ini tidak akan match (status sudah bukan 'pending' lagi) jadi
     * 0 baris ke-update, status "paid" yang sudah benar tidak akan tertimpa.
     */
    public function status(string $orderId): JsonResponse
    {
        $payment = FormPayment::where('order_id', $orderId)->firstOrFail();

        if ($payment->status === 'pending' && $payment->expires_at && now()->greaterThanOrEqualTo($payment->expires_at)) {
            $expired = FormPayment::where('id', $payment->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            if ($expired === 1) {
                $payment->status = 'expired';
            }
        }

        return response()->json([
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'expires_at' => optional($payment->expires_at)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Tempat gateway redirect balik BROWSER user setelah selesai bayar.
     * Ini murni UX (bukan sumber kebenaran status) — cuma balik ke wizard
     * dengan order_id di query string supaya polling status jalan lagi.
     */
    public function return(Request $request): RedirectResponse
    {
        $orderId = $request->query('order_id');
        $payment = $orderId ? FormPayment::where('order_id', $orderId)->first() : null;

        return redirect()->route('frontend.form.wizard', [
            'form_id' => $payment->form_id ?? null,
            'order_id' => $orderId,
        ]);
    }

    public function midtransWebhook(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, fn () => $request->input('order_id'));
    }

    public function duitkuWebhook(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, fn () => $request->input('merchantOrderId'));
    }

    public function ipaymuWebhook(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, fn () => $request->input('reference_id'));
    }

    /**
     * Alur umum semua webhook gateway:
     *   1. Ambil order_id dari BODY payload (bukan dari URL) supaya kita
     *      selalu tahu transaksi mana yang dimaksud.
     *   2. Muat FormPayment + config PaymentGateway MILIK transaksi itu
     *      sendiri (bukan dari payload) untuk verifikasi signature — supaya
     *      orang tidak bisa kirim callback palsu mengklaim gateway lain.
     *   3. Idempotent: transaksi yang sudah "paid" tidak diproses ulang.
     */
    private function handleWebhook(Request $request, Closure $resolveOrderId): JsonResponse
    {
        $orderId = (string) $resolveOrderId();

        if ($orderId === '') {
            return response()->json(['message' => 'order_id tidak ditemukan pada payload.'], 400);
        }

        $payment = FormPayment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('[PAYMENT] Webhook diterima untuk order_id yang tidak dikenal', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        if ($payment->isPaid()) {
            return response()->json(['message' => 'OK (sudah diproses sebelumnya)']);
        }

        $gateway = $payment->paymentGateway;

        if (!$gateway) {
            Log::error('[PAYMENT] FormPayment tanpa payment_gateway_id, tidak bisa verifikasi signature', [
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Konfigurasi gateway tidak ditemukan.'], 500);
        }

        try {
            $driver = PaymentGatewayFactory::make($gateway);
            $result = $driver->handleCallback($request);
        } catch (PaymentSignatureMismatchException $e) {
            Log::warning('[PAYMENT] Signature callback tidak valid, transaksi TIDAK diubah', [
                'order_id' => $orderId,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $payment->update([
            'gateway_reference' => $result['reference'] ?? $payment->gateway_reference,
            'raw_callback' => $result['raw'] ?? null,
            'status' => $result['is_paid'] ? 'paid' : ($result['is_failed'] ? 'failed' : $payment->status),
            'paid_at' => $result['is_paid'] ? now() : $payment->paid_at,
        ]);

        Log::info('[PAYMENT] Webhook diproses', [
            'order_id' => $orderId,
            'gateway' => $gateway->gateway,
            'status' => $payment->fresh()->status,
        ]);

        return response()->json(['message' => 'OK']);
    }

    private function generateOrderId(): string
    {
        do {
            $orderId = 'INA' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (FormPayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
