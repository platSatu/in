<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositPayment;
use App\Models\Transaction;
use App\Services\DepositPayment\DepositPaymentGatewayFactory;
use App\Services\Payment\PaymentSignatureMismatchException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Webhook SERVER-TO-SERVER dari gateway pembayaran (bukan browser user) --
 * SATU-SATUNYA tempat topup saldo boleh benar-benar mengkredit `deposits` +
 * `transactions`. Polanya (verifikasi signature dulu, baru proses;
 * idempotent; row-locking) sengaja disamakan dengan
 * App\Http\Controllers\Payment\FormPaymentController::handleWebhook(), tapi
 * jalur kodenya independen 100% (lihat App\Services\DepositPayment\*) --
 * keputusan owner supaya fitur topup saldo ini tidak menyentuh sama sekali
 * alur pembayaran Form/Quiz yang sudah live.
 */
class DepositWebhookController extends Controller
{
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

    private function handleWebhook(Request $request, Closure $resolveOrderId): JsonResponse
    {
        $orderId = (string) $resolveOrderId();

        if ($orderId === '') {
            return response()->json(['message' => 'order_id tidak ditemukan pada payload.'], 400);
        }

        $payment = DepositPayment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('[DEPOSIT] Webhook diterima untuk order_id yang tidak dikenal', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        // Idempotency check #1 (di luar transaction, cepat): transaksi yang
        // sudah 'paid' tidak perlu diproses ulang sama sekali. Cek KEDUA
        // (di dalam transaction + row lock) di bawah adalah penjaga
        // sebenarnya terhadap race condition -- yang ini murni optimisasi
        // supaya retry webhook yang jelas-jelas sudah selesai tidak perlu
        // buka transaction & verifikasi signature lagi.
        if ($payment->isPaid()) {
            return response()->json(['message' => 'OK (sudah diproses sebelumnya)']);
        }

        $gateway = $payment->paymentGateway;

        if (!$gateway) {
            Log::error('[DEPOSIT] DepositPayment tanpa payment_gateway_id, tidak bisa verifikasi signature', [
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Konfigurasi gateway tidak ditemukan.'], 500);
        }

        try {
            $driver = DepositPaymentGatewayFactory::make($gateway);
            $result = $driver->handleCallback($request);
        } catch (PaymentSignatureMismatchException $e) {
            Log::warning('[DEPOSIT] Signature callback tidak valid, transaksi TIDAK diubah', [
                'order_id' => $orderId,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if (!$result['is_paid']) {
            // Gagal/dibatalkan di sisi gateway -- catat statusnya, TIDAK ada
            // apa pun yang perlu di-kredit, jadi tidak butuh DB transaction.
            $payment->update([
                'gateway_reference' => $result['reference'] ?? $payment->gateway_reference,
                'raw_callback' => $result['raw'] ?? null,
                'status' => $result['is_failed'] ? 'failed' : $payment->status,
            ]);

            Log::info('[DEPOSIT] Webhook diproses (bukan pembayaran sukses)', [
                'order_id' => $orderId,
                'gateway' => $gateway->gateway,
                'status' => $payment->fresh()->status,
            ]);

            return response()->json(['message' => 'OK']);
        }

        // === TITIK PALING SENSITIF: pengkreditan saldo ===
        // Dibungkus DB::transaction() supaya SEMUA perubahan (update status
        // DepositPayment, insert Deposit, insert Transaction) sukses/gagal
        // bersama-sama (auto COMMIT kalau closure selesai tanpa exception,
        // auto ROLLBACK kalau ada yang melempar exception) -- tidak pernah
        // ada kondisi "saldo sudah nambah tapi DepositPayment masih pending"
        // atau sebaliknya.
        //
        // lockForUpdate() dipasang di 2 baris: baris DepositPayment ini
        // sendiri (idempotency check #2, race-safe terhadap 2 webhook retry
        // yang datang nyaris bersamaan) DAN baris Deposit TERAKHIR milik
        // user ini (supaya "saldo sebelum" yang dipakai untuk hitung "saldo
        // sesudah" selalu yang paling baru, tidak ada 2 topup bersamaan yang
        // sama-sama baca saldo lama lalu sama-sama nulis balance yang salah).
        DB::transaction(function () use ($payment, $result, $gateway): void {
            $lockedPayment = DepositPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment || $lockedPayment->status === 'paid') {
                // Sudah diproses webhook lain barusan (race condition) --
                // tidak boleh kredit dua kali, cukup berhenti di sini.
                return;
            }

            $userId = $lockedPayment->user_id;

            DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            $lastDeposit = Deposit::query()
                ->where('user_id', $userId)
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $balanceBefore = (float) ($lastDeposit?->balance ?? 0);
            $amount = (float) $lockedPayment->amount;
            $balanceAfter = $balanceBefore + $amount;

            $methodLabel = $lockedPayment->payment_method ?: $lockedPayment->gateway;

            $deposit = Deposit::create([
                'user_id' => $userId,
                'debit' => 0,
                'kredit' => $amount,
                'balance' => $balanceAfter,
                'description' => "Topup saldo via {$gateway->gateway} (order {$lockedPayment->order_id})",
                'payment_status' => 'success',
                'payment_method' => $methodLabel,
                'payment_date' => now(),
            ]);

            Transaction::create([
                'transaction_code' => 'TRX-' . now()->format('YmdHisv') . '-' . strtoupper(Str::random(6)),
                'user_id' => $userId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Topup saldo via {$gateway->gateway}",
                'reference_type' => 'deposit_payment',
                'reference_id' => (string) $lockedPayment->id,
                'status' => 'success',
                'channel' => $gateway->gateway,
                'metadata' => [
                    'order_id' => $lockedPayment->order_id,
                    'payment_method' => $methodLabel,
                    'gateway_reference' => $result['reference'] ?? null,
                    'source' => 'dashboard.deposit.webhook',
                ],
                'created_by' => $userId,
                'transaction_date' => now(),
            ]);

            $lockedPayment->update([
                'gateway_reference' => $result['reference'] ?? $lockedPayment->gateway_reference,
                'raw_callback' => $result['raw'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('[DEPOSIT] Saldo berhasil dikreditkan lewat webhook', [
                'order_id' => $lockedPayment->order_id,
                'user_id' => $userId,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'deposit_id' => $deposit->id,
            ]);
        });

        return response()->json(['message' => 'OK']);
    }
}
