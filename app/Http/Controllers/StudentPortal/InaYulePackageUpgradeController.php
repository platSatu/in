<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\PaymentGateway;
use App\Models\Student;
use App\Services\CoursePackagePayment\CoursePackagePaymentGatewayFactory;
use App\Services\CoursePackagePayment\CoursePackagePurchaseNotifier;
use App\Services\CoursePackagePayment\PackageUpgradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * FASE 4 bagian 2 "Konversi/Upgrade Paket" (16 September 2026) -- controller
 * yang benar-benar dipanggil student untuk upgrade ke package lain. SEMUA
 * perhitungan (trade-in, Deposit, gateway) dilakukan
 * App\Services\CoursePackagePayment\PackageUpgradeCalculator -- controller
 * ini murni urusan HTTP (tampilkan halaman, terima submit, redirect ke
 * gateway), pola SAMA PERSIS dengan InaYulePackageCheckoutController.
 *
 * DUA JALUR PENYELESAIAN (ditentukan preview()->gateway_portion, DIHITUNG
 * ULANG di server tiap request, TIDAK PERNAH percaya angka dari browser):
 *
 * 1. gateway_portion = 0 (trade-in + saldo Deposit sudah cukup) -- diproses
 *    INSTAN lewat PackageUpgradeCalculator::completeInstant().
 * 2. gateway_portion > 0 -- initiateGatewayCheckout() DI SINI cuma membuat
 *    baris CoursePackagePayment 'pending' dengan credit_trade_in_portion
 *    dicatat sebagai ANGKA RENCANA saja (SAMA seperti deposit_portion di
 *    InaYulePackageCheckoutController::initiateGatewayCheckout()) --
 *    credit lama BELUM disentuh sama sekali sampai gateway konfirmasi
 *    'paid' lewat App\Http\Controllers\StudentPortal\
 *    InaYulePackageWebhookController::processUpgradeConfirmation(), yang
 *    RE-CHECK ulang nilai trade-in dari 0 di dalam lock (bisa mengecil
 *    kalau credit lama sempat terpakai untuk sesi kelas di antaranya).
 *
 * Route select-method/return/status SENGAJA memakai yang sudah ada di
 * InaYulePackageCheckoutController -- tidak ada logika di situ yang
 * spesifik ke checkout biasa, keduanya sama-sama beroperasi murni lewat
 * order_id pada CoursePackagePayment.
 */
class InaYulePackageUpgradeController extends Controller
{
    public function __construct(
        private readonly PackageUpgradeCalculator $calculator = new PackageUpgradeCalculator()
    ) {
    }

    public function show(Request $request, string $packageId): View|RedirectResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('inayule.index')->with('status', 'Data student untuk akun ini tidak ditemukan.');
        }

        $targetPackage = CoursePackage::where('status', 'active')->find($packageId);

        if (!$targetPackage) {
            return redirect()->route('inayule.index')->with('status', 'Package tidak ditemukan atau sudah tidak aktif.');
        }

        $preview = $this->calculator->preview($user, $student, $targetPackage);

        $activeGateway = $preview['gateway_portion'] > 0
            ? PaymentGateway::where('is_active', true)->where('status', 'active')->latest('updated_at')->first()
            : null;

        return view('student-portal.inayule.upgrade', [
            'package' => $targetPackage,
            'preview' => $preview,
            'gatewayMissing' => $preview['gateway_portion'] > 0 && !$activeGateway,
        ]);
    }

    public function store(Request $request, string $packageId): RedirectResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('inayule.index')->with('status', 'Data student untuk akun ini tidak ditemukan.');
        }

        $targetPackage = CoursePackage::where('status', 'active')->find($packageId);

        if (!$targetPackage) {
            return redirect()->route('inayule.index')->with('status', 'Package tidak ditemukan atau sudah tidak aktif.');
        }

        // Preview (belum di-lock) HANYA untuk memilih jalur -- kebenaran
        // akhirnya SELALU dicek ulang di dalam lock, di completeInstant()
        // atau di webhook untuk jalur gateway. Pola sama persis
        // InaYulePackageCheckoutController::store().
        $preview = $this->calculator->preview($user, $student, $targetPackage);

        if ($preview['gateway_portion'] <= 0.0) {
            $outcome = $this->calculator->completeInstant($user, $student, $targetPackage);

            if ($outcome['ok']) {
                (new CoursePackagePurchaseNotifier())->notify($outcome['payment']);

                return redirect()->route('inayule.index')->with('success', 'Upgrade ke package "' . $targetPackage->name . '" berhasil, credit Anda sudah diperbarui.');
            }

            // Saldo/credit berubah tepat di antara preview & lock (race
            // jarang) -- paling aman minta user coba lagi.
            return redirect()->route('inayule.upgrade.show', ['packageId' => $targetPackage->id])
                ->with('error', 'Saldo/credit Anda baru saja berubah, silakan coba lagi.');
        }

        return $this->initiateGatewayCheckout($user, $student, $targetPackage, $preview);
    }

    /**
     * Jalur gateway (trade-in + Deposit belum cukup menutup 100% harga) --
     * lihat docblock class di atas (poin 2). TIDAK menyentuh CourseCredit
     * ATAU Deposit sama sekali di sini.
     *
     * @param array{target_price: float, deposit_portion: float, gateway_portion: float, trade_in_applied_to_price: float} $preview
     */
    private function initiateGatewayCheckout(mixed $user, Student $student, CoursePackage $targetPackage, array $preview): RedirectResponse
    {
        $activeGateway = PaymentGateway::where('is_active', true)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (!$activeGateway) {
            return redirect()->route('inayule.upgrade.show', ['packageId' => $targetPackage->id])
                ->with('error', 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.');
        }

        $payment = CoursePackagePayment::create([
            'student_id' => $student->id,
            'user_id' => (string) $user->id,
            'course_package_id' => $targetPackage->id,
            'payment_gateway_id' => $activeGateway->id,
            'order_id' => $this->generateOrderId(),
            'gateway' => $activeGateway->gateway,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'handphone' => $user->handphone,
            'price_total' => $preview['target_price'],
            'deposit_portion' => $preview['deposit_portion'],
            'gateway_portion' => $preview['gateway_portion'],
            // Trade-in DICATAT sebagai ANGKA RENCANA saja di sini -- credit
            // lama belum disentuh sampai gateway konfirmasi, lihat docblock
            // InaYulePackageWebhookController::processUpgradeConfirmation().
            'credit_trade_in_portion' => $preview['trade_in_applied_to_price'],
            'credits_granted' => $targetPackage->credits,
            'status' => CoursePackagePayment::STATUS_PENDING,
            'expires_at' => now()->addMinutes($activeGateway->expiry_minutes ?? 60),
        ]);

        try {
            $driver = CoursePackagePaymentGatewayFactory::make($activeGateway);

            if ($driver->requiresMethodSelection()) {
                return redirect()->route('inayule.checkout.select-method', ['orderId' => $payment->order_id]);
            }

            $result = $driver->createTransaction($payment);

            $payment->update([
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            if (empty($result['redirect_url'])) {
                throw new RuntimeException('Gateway tidak mengembalikan URL pembayaran.');
            }

            return redirect()->away($result['redirect_url']);
        } catch (Throwable $e) {
            Log::error('[COURSE-PACKAGE][UPGRADE] Gagal init transaksi upgrade', [
                'order_id' => $payment->order_id,
                'gateway' => $activeGateway->gateway,
                'message' => $e->getMessage(),
            ]);

            $payment->update(['status' => CoursePackagePayment::STATUS_FAILED]);

            return redirect()->route('inayule.upgrade.show', ['packageId' => $targetPackage->id])
                ->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    private function generateOrderId(): string
    {
        do {
            $orderId = 'CPPU' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (CoursePackagePayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
