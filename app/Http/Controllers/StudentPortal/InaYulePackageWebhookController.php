<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\CoursePackagePayment\CoursePackagePaymentGatewayFactory;
use App\Services\CoursePackagePayment\CoursePackagePurchaseNotifier;
use App\Services\Payment\PaymentSignatureMismatchException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Webhook SERVER-TO-SERVER dari gateway pembayaran untuk checkout package
 * (bukan browser user) -- SATU-SATUNYA tempat porsi gateway/campuran boleh
 * benar-benar mengkredit CourseCredit + (kalau ada deposit_portion) mendebit
 * Deposit. Polanya (verifikasi signature dulu, baru proses; idempotent;
 * row-locking) SAMA PERSIS dengan DepositWebhookController, jalur kodenya
 * independen 100% (lihat App\Services\CoursePackagePayment\*) -- konsisten
 * dengan keputusan owner yang sudah dipakai 2x sebelumnya di project ini.
 *
 * FAIL-SAFE PALING PENTING (khusus di sini, tidak ada di DepositWebhookController
 * karena topup saldo tidak punya konsep "campuran"): deposit_portion yang
 * dicatat saat checkout dibuat HANYA rencana -- saldo Deposit di-RE-CHECK
 * ULANG di sini, di dalam lock, tepat sebelum benar-benar didebit. Kalau
 * ternyata sudah tidak cukup (mis. user checkout 2 package hampir bersamaan
 * dan salah satu debitnya lebih dulu commit), pembelian ini SENGAJA
 * digagalkan total (status 'failed', TIDAK ada CourseCredit yang di-grant
 * sama sekali) -- bukan digranting sebagian -- dan dicatat Log::critical()
 * untuk direkonsiliasi manual admin, karena porsi gateway-nya sudah
 * terlanjur terbayar sungguhan ke payment gateway walau porsi deposit-nya
 * gagal. Kasus ini seharusnya sangat jarang terjadi.
 */
class InaYulePackageWebhookController extends Controller
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

        $payment = CoursePackagePayment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('[COURSE-PACKAGE] Webhook diterima untuk order_id yang tidak dikenal', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        // Idempotency check #1 (di luar transaction, cepat) -- lihat
        // docblock DepositWebhookController::handleWebhook() untuk alasan
        // lengkapnya, sama persis di sini.
        if ($payment->isPaid()) {
            return response()->json(['message' => 'OK (sudah diproses sebelumnya)']);
        }

        $gateway = $payment->paymentGateway;

        if (!$gateway) {
            Log::error('[COURSE-PACKAGE] CoursePackagePayment tanpa payment_gateway_id, tidak bisa verifikasi signature', [
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Konfigurasi gateway tidak ditemukan.'], 500);
        }

        try {
            $driver = CoursePackagePaymentGatewayFactory::make($gateway);
            $result = $driver->handleCallback($request);
        } catch (PaymentSignatureMismatchException $e) {
            Log::warning('[COURSE-PACKAGE] Signature callback tidak valid, transaksi TIDAK diubah', [
                'order_id' => $orderId,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if (!$result['is_paid']) {
            // Gagal/dibatalkan di sisi gateway -- catat statusnya. Deposit
            // belum pernah disentuh untuk order ini (lihat docblock
            // InaYulePackageCheckoutController), jadi tidak ada apa pun
            // yang perlu di-rollback/refund, cukup update status.
            $payment->update([
                'gateway_reference' => $result['reference'] ?? $payment->gateway_reference,
                'raw_callback' => $result['raw'] ?? null,
                'status' => $result['is_failed'] ? CoursePackagePayment::STATUS_FAILED : $payment->status,
            ]);

            Log::info('[COURSE-PACKAGE] Webhook diproses (bukan pembayaran sukses)', [
                'order_id' => $orderId,
                'gateway' => $gateway->gateway,
                'status' => $payment->fresh()->status,
            ]);

            return response()->json(['message' => 'OK']);
        }

        // === TITIK PALING SENSITIF: pengkreditan credit + (kalau ada) pendebitan Deposit ===
        $outcome = DB::transaction(function () use ($payment, $result): array {
            $lockedPayment = CoursePackagePayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$lockedPayment || $lockedPayment->status === CoursePackagePayment::STATUS_PAID) {
                // Sudah diproses webhook lain barusan (race condition).
                return ['status' => 'already_processed'];
            }

            $userId = $lockedPayment->user_id;
            $studentId = $lockedPayment->student_id;

            DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            $lockedStudent = Student::where('id', $studentId)->lockForUpdate()->first();

            $depositPortion = (float) $lockedPayment->deposit_portion;
            $depositBalanceBefore = null;
            $depositBalanceAfter = null;

            if ($depositPortion > 0) {
                $lastDeposit = Deposit::where('user_id', $userId)
                    ->orderByDesc('payment_date')
                    ->orderByDesc('created_at')
                    ->lockForUpdate()
                    ->first();

                $depositBalanceBefore = (float) ($lastDeposit?->balance ?? 0);

                if ($depositBalanceBefore < $depositPortion) {
                    // FAIL-SAFE -- lihat docblock class di atas.
                    $lockedPayment->update([
                        'status' => CoursePackagePayment::STATUS_FAILED,
                        'gateway_reference' => $result['reference'] ?? $lockedPayment->gateway_reference,
                        'raw_callback' => $result['raw'] ?? null,
                    ]);

                    Log::critical('[COURSE-PACKAGE] Saldo Deposit tidak cukup saat webhook konfirmasi -- porsi gateway SUDAH terbayar tapi credit TIDAK di-grant, BUTUH REKONSILIASI MANUAL ADMIN', [
                        'order_id' => $lockedPayment->order_id,
                        'user_id' => $userId,
                        'student_id' => $studentId,
                        'deposit_portion_dibutuhkan' => $depositPortion,
                        'saldo_deposit_tersedia' => $depositBalanceBefore,
                        'gateway_portion_sudah_dibayar' => (float) $lockedPayment->gateway_portion,
                    ]);

                    return ['status' => 'insufficient_deposit'];
                }

                $depositBalanceAfter = $depositBalanceBefore - $depositPortion;
            }

            $package = CoursePackage::find($lockedPayment->course_package_id);
            $packageName = $package->name ?? 'Package';

            if ($depositPortion > 0) {
                Deposit::create([
                    'user_id' => $userId,
                    'debit' => $depositPortion,
                    'kredit' => 0,
                    'balance' => $depositBalanceAfter,
                    'description' => "Beli package: {$packageName} (order {$lockedPayment->order_id})",
                    'payment_status' => 'success',
                    'payment_method' => 'saldo',
                    'payment_date' => now(),
                ]);

                Transaction::create([
                    'transaction_code' => 'TRX-' . now()->format('YmdHisv') . '-' . strtoupper(Str::random(6)),
                    'user_id' => $userId,
                    'type' => 'debit',
                    'amount' => $depositPortion,
                    'balance_before' => $depositBalanceBefore,
                    'balance_after' => $depositBalanceAfter,
                    'description' => "Beli package: {$packageName} (porsi saldo)",
                    'reference_type' => 'course_package_payment',
                    'reference_id' => $lockedPayment->order_id,
                    'status' => 'success',
                    'channel' => 'saldo',
                    'metadata' => [
                        'order_id' => $lockedPayment->order_id,
                        'course_package_id' => $lockedPayment->course_package_id,
                        'gateway_portion' => (float) $lockedPayment->gateway_portion,
                        'source' => 'inayule.checkout.webhook',
                    ],
                    'created_by' => $userId,
                    'transaction_date' => now(),
                ]);
            }

            $source = $depositPortion <= 0
                ? CoursePackagePurchase::SOURCE_GATEWAY_PURCHASE
                : CoursePackagePurchase::SOURCE_MIXED_PURCHASE;

            $purchase = CoursePackagePurchase::create([
                'student_id' => $studentId,
                'course_package_id' => $lockedPayment->course_package_id,
                'price_paid' => $lockedPayment->price_total,
                'credits_granted' => $lockedPayment->credits_granted,
                'source' => $source,
                'status' => CoursePackagePurchase::STATUS_COMPLETED,
            ]);

            $newCreditBalance = CourseCredit::currentBalanceFor($studentId) + (float) $lockedPayment->credits_granted;

            CourseCredit::create([
                'student_id' => $studentId,
                'course_package_purchase_id' => $purchase->id,
                'source_type' => CourseCredit::SOURCE_PURCHASE,
                'debit' => 0,
                'kredit' => $lockedPayment->credits_granted,
                'balance' => $newCreditBalance,
                'description' => 'Beli package: ' . $packageName,
            ]);

            $lockedPayment->update([
                'gateway_reference' => $result['reference'] ?? $lockedPayment->gateway_reference,
                'raw_callback' => $result['raw'] ?? null,
                'status' => CoursePackagePayment::STATUS_PAID,
                'paid_at' => now(),
                'course_package_purchase_id' => $purchase->id,
            ]);

            Log::info('[COURSE-PACKAGE] Purchase berhasil diproses lewat webhook', [
                'order_id' => $lockedPayment->order_id,
                'user_id' => $userId,
                'student_id' => $studentId,
                'deposit_portion' => $depositPortion,
                'gateway_portion' => (float) $lockedPayment->gateway_portion,
                'purchase_id' => $purchase->id,
            ]);

            return ['status' => 'paid', 'payment_id' => $lockedPayment->id];
        });

        // Notifikasi WA dikirim SETELAH transaction commit, TIDAK di
        // dalamnya -- lihat docblock CoursePackagePurchaseNotifier.
        if (($outcome['status'] ?? null) === 'paid') {
            $freshPayment = CoursePackagePayment::find($outcome['payment_id']);

            if ($freshPayment) {
                (new CoursePackagePurchaseNotifier())->notify($freshPayment);
            }
        }

        return response()->json(['message' => 'OK']);
    }
}
