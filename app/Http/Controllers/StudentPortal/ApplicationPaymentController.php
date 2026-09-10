<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationPayment;
use App\Models\PaymentGateway;
use App\Models\UniversityApplication;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Fase 2 -- fitur "Alur Pembayaran 2 Arah Apply Kampus" (10 September 2026).
 *
 * Gerbang pembayaran Registration Fee (Step 3, sebelum Study Plan & Upload
 * Documents terbuka) DAN Departure Fee (Step 4, setelah semua dokumen
 * di-approve admin -- lihat Fase 5). Satu controller dipakai untuk 2
 * "purpose" ini supaya tidak duplikasi kode.
 *
 * Meniru pola Payment\FormPaymentController (init/status/return) TAPI
 * SENGAJA di namespace StudentPortal terpisah (bukan menambah method di
 * FormPaymentController) karena: (1) route ini WAJIB login (siswa yang
 * apply), beda dari FormPaymentController yang publik/guest; (2) perlu
 * pengecekan kepemilikan aplikasi per user, sama seperti
 * ApplicationDocumentController::ownedApplicationOrFail(). Webhook TETAP
 * satu-satunya di Payment\FormPaymentController (lihat catatan
 * resolvePaymentByOrderId() di sana) -- controller ini TIDAK punya method
 * webhook sendiri.
 */
class ApplicationPaymentController extends Controller
{
    private const PURPOSES = [
        ApplicationPayment::PURPOSE_REGISTRATION_FEE,
        ApplicationPayment::PURPOSE_DEPARTURE_FEE,
    ];

    public function show(Request $request, string $applicationId, string $purpose): View|RedirectResponse
    {
        $application = $this->ownedApplicationOrFail($request, $applicationId);
        abort_unless(in_array($purpose, self::PURPOSES, true), 404);

        // Sudah lunas -- tidak perlu balik ke halaman bayar, arahkan ke
        // tujuan berikutnya (Study Plan/Documents untuk registration_fee;
        // ringkasan aplikasi untuk departure_fee -- belum ada halaman
        // "sesudah lunas" khusus Fase 5, menyusul nanti).
        $alreadyPaid = ApplicationPayment::where('application_id', $application->id)
            ->where('purpose', $purpose)
            ->where('status', ApplicationPayment::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return redirect()->route(
                $purpose === ApplicationPayment::PURPOSE_REGISTRATION_FEE
                    ? 'student-portal.applications.documents.edit'
                    : 'student-portal.applications.show',
                $application->id
            );
        }

        // FASE 5: Departure Fee (Step 4) SEKARANG mensyaratkan aplikasi sudah
        // "ACCEPTED" (bukan cuma admission_status terisi/under_review) --
        // ACCEPTED cuma diset admin secara manual setelah dokumen (Study
        // Plan, Upload Documents, dst) sudah di-review/approve semua dan
        // Offer Letter/Passport dari InaStudy sudah terbit, lihat
        // Quiz\UniversityApplicationController::updateAdmissionStatus() &
        // tracker Under Review -> Processing -> Accepted di halaman
        // ringkasan siswa. Kalau belum diisi SAMA SEKALI (masih kosong),
        // artinya Registration Fee juga belum lunas -- arahkan ke situ dulu
        // (pesan beda supaya jelas bedanya dengan "menunggu review admin").
        if ($purpose === ApplicationPayment::PURPOSE_DEPARTURE_FEE
            && $application->admission_status !== UniversityApplication::ADMISSION_STATUS_ACCEPTED) {
            if (empty($application->admission_status)) {
                return redirect()
                    ->route('student-portal.applications.payment.show', [$application->id, ApplicationPayment::PURPOSE_REGISTRATION_FEE])
                    ->with('status', 'Selesaikan pembayaran Registration Fee terlebih dahulu.');
            }

            return redirect()
                ->route('student-portal.applications.show', $application->id)
                ->with('status', 'Departure Fee bisa dibayar setelah aplikasi Anda berstatus "Accepted". Silakan tunggu proses review dokumen oleh tim kami.');
        }

        $amount = $purpose === ApplicationPayment::PURPOSE_REGISTRATION_FEE
            ? $application->registration_fee_amount
            : $application->deposit_fee_china_amount;

        $pendingPayment = ApplicationPayment::where('application_id', $application->id)
            ->where('purpose', $purpose)
            ->whereIn('status', [ApplicationPayment::STATUS_PENDING])
            ->latest('created_at')
            ->first();

        return view('student-portal.applications.payment', [
            'application' => $application,
            'purpose' => $purpose,
            'purposeLabel' => $purpose === ApplicationPayment::PURPOSE_REGISTRATION_FEE ? 'Registration Fee' : 'Departure Fee',
            'amount' => $amount,
            'pendingPayment' => $pendingPayment,
        ]);
    }

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'uuid', 'exists:university_applications,id'],
            'purpose' => ['required', Rule::in(self::PURPOSES)],
        ]);

        $application = $this->ownedApplicationOrFail($request, $validated['application_id']);

        // FASE 5: gerbang SEBENARNYA (bukan cuma redirect di show()) --
        // transaksi Departure Fee ditolak di sini kalau admission_status
        // aplikasi ini belum "accepted", supaya siswa tidak bisa
        // menyelundupkan POST langsung ke endpoint ini (mis. lewat
        // devtools/curl) untuk melewati tampilan halaman payment.blade.php.
        if ($validated['purpose'] === ApplicationPayment::PURPOSE_DEPARTURE_FEE
            && $application->admission_status !== UniversityApplication::ADMISSION_STATUS_ACCEPTED) {
            return response()->json([
                'message' => 'Departure Fee belum bisa dibayar. Aplikasi Anda harus berstatus "Accepted" terlebih dahulu.',
            ], 422);
        }

        $amount = $validated['purpose'] === ApplicationPayment::PURPOSE_REGISTRATION_FEE
            ? $application->registration_fee_amount
            : $application->deposit_fee_china_amount;

        // FIX: nominal KEDUA purpose ini diisi manual oleh admin per
        // aplikasi (bukan otomatis dari data kampus/major) -- kalau admin
        // belum sempat mengisi, tolak dengan pesan jelas alih-alih transaksi
        // Rp 0.
        if (empty($amount) || $amount <= 0) {
            return response()->json([
                'message' => 'Nominal pembayaran belum diatur oleh admin. Silakan hubungi admin kami.',
            ], 422);
        }

        $alreadyPaid = ApplicationPayment::where('application_id', $application->id)
            ->where('purpose', $validated['purpose'])
            ->where('status', ApplicationPayment::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json(['message' => 'Pembayaran ini sudah lunas.'], 422);
        }

        // Gateway aktif system-wide -- SAMA PERSIS query yang dipakai
        // FormPaymentController::init() (satu gateway aktif berlaku untuk
        // seluruh fitur pembayaran, Quiz maupun Apply Kampus).
        $gateway = PaymentGateway::where('is_active', true)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (!$gateway) {
            return response()->json([
                'message' => 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.',
            ], 422);
        }

        $payment = ApplicationPayment::create([
            'application_id' => $application->id,
            'purpose' => $validated['purpose'],
            'payment_gateway_id' => $gateway->id,
            'order_id' => $this->generateOrderId(),
            'gateway' => $gateway->gateway,
            'amount' => $amount,
            'status' => ApplicationPayment::STATUS_PENDING,
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
            Log::error('[APPLICATION-PAYMENT] Gagal init transaksi', [
                'order_id' => $payment->order_id,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            $payment->update(['status' => ApplicationPayment::STATUS_FAILED]);

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran, silakan coba lagi.'], 500);
        }
    }

    public function selectDuitkuMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'exists:application_payments,order_id'],
            'payment_method' => ['required', 'string', 'max:10'],
        ]);

        $payment = ApplicationPayment::where('order_id', $validated['order_id'])
            ->where('status', ApplicationPayment::STATUS_PENDING)
            ->firstOrFail();

        $this->assertOwnsPayment($request, $payment);

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
            Log::error('[APPLICATION-PAYMENT][Duitku] Gagal buat transaksi setelah pilih metode', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal membuat transaksi pembayaran, silakan coba lagi.'], 500);
        }
    }

    /**
     * Sama persis logika self-heal expired di FormPaymentController::status()
     * -- lihat komentar lengkap di sana.
     */
    public function status(Request $request, string $orderId): JsonResponse
    {
        $payment = ApplicationPayment::where('order_id', $orderId)->firstOrFail();

        $this->assertOwnsPayment($request, $payment);

        if ($payment->status === ApplicationPayment::STATUS_PENDING
            && $payment->expires_at
            && now()->greaterThanOrEqualTo($payment->expires_at)) {
            $expired = ApplicationPayment::where('id', $payment->id)
                ->where('status', ApplicationPayment::STATUS_PENDING)
                ->update(['status' => ApplicationPayment::STATUS_EXPIRED]);

            if ($expired === 1) {
                $payment->status = ApplicationPayment::STATUS_EXPIRED;
            }
        }

        return response()->json([
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'expires_at' => optional($payment->expires_at)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function return(Request $request): RedirectResponse
    {
        $orderId = $request->query('order_id');
        $payment = $orderId ? ApplicationPayment::where('order_id', $orderId)->first() : null;

        if (!$payment) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('student-portal.applications.payment.show', [$payment->application_id, $payment->purpose])
            ->with('resume_order_id', $orderId);
    }

    /**
     * Sama persis pola ApplicationDocumentController::ownedApplicationOrFail().
     */
    private function ownedApplicationOrFail(Request $request, string $applicationId): UniversityApplication
    {
        $application = UniversityApplication::findOrFail($applicationId);

        $user = $request->user();
        $ownsApplication = $application->student && $application->student->user_id === $user->id;

        abort_unless($ownsApplication, Response::HTTP_FORBIDDEN);

        return $application;
    }

    private function assertOwnsPayment(Request $request, ApplicationPayment $payment): void
    {
        $application = $payment->application;
        $user = $request->user();

        abort_unless(
            $application && $application->student && $application->student->user_id === $user->id,
            Response::HTTP_FORBIDDEN
        );
    }

    private function generateOrderId(): string
    {
        do {
            // Prefix "INAAPP" (beda dari FormPayment yang "INA") -- murni
            // biar gampang dibedakan di log/dashboard gateway, BUKAN dipakai
            // logic apa pun untuk membedakan tabel (resolvePaymentByOrderId()
            // di FormPaymentController tetap coba kedua tabel apa adanya).
            $orderId = 'INAAPP' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (ApplicationPayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
