<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\PaymentGateway;
use App\Models\Student;
use App\Services\CourseCredit\CourseCreditDebitService;
use App\Services\CoursePackagePayment\CoursePackagePaymentGatewayFactory;
use App\Services\CoursePackagePayment\CoursePackagePurchaseNotifier;
use App\Services\CoursePackagePayment\PackageUpgradeCalculator;
use App\Services\CoursePackagePayment\UpgradeConfirmationFailedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Upgrade / Convert dari 1 baris paket di tab Status. Semua hitungan di
 * App\Services\CoursePackagePayment\PackageUpgradeCalculator; controller ini
 * hanya HTTP. Angka SELALU dihitung ulang di server, tidak pernah dari browser.
 *
 * - convert (quantity N / "Tukar Semua") dan upgrade yang tertutup trade-in + saldo: instan.
 * - upgrade yang masih kurang: CoursePackagePayment 'pending' ke gateway;
 *   credit asal dikunci sampai webhook (InaYulePackageWebhookController).
 * Route select-method/return/status memakai milik InaYulePackageCheckoutController.
 */
class InaYulePackageUpgradeController extends Controller
{
    private const FAILURE_MESSAGES = [
        'insufficient_trade_in' => 'Sisa credit paket ini baru saja berubah, silakan coba lagi.',
        'insufficient_deposit' => 'Saldo Anda baru saja berubah, silakan coba lagi.',
    ];

    public function __construct(
        private readonly PackageUpgradeCalculator $calculator = new PackageUpgradeCalculator()
    ) {
    }

    public function show(Request $request, string $purchaseId): View|RedirectResponse
    {
        $source = $this->ownedPurchase($request, $purchaseId);

        if (! $source) {
            return redirect()->route('inayule.index')->with('status', 'Paket tidak ditemukan.');
        }

        $mode = $request->query('mode') === 'convert' ? 'convert' : 'upgrade';
        $packageId = $request->query('package');
        $target = is_string($packageId) && $packageId !== '' ? $this->activePackage($packageId, $source) : null;
        $convertAll = $mode === 'convert' && $request->boolean('all');
        $quantity = $mode === 'convert' && ! $convertAll ? max(1, (int) $request->query('quantity', 1)) : null;
        $preview = null;
        $error = $this->calculator->blockedReason($source);

        if ($target && ! $error) {
            try {
                $preview = $this->calculator->preview($request->user(), $source, $target, $quantity, $convertAll);
            } catch (UpgradeConfirmationFailedException $e) {
                $error = $e->getMessage();
            }
        }

        return view('student-portal.inayule.upgrade', [
            'source' => $source->load('coursePackage.courseClass'),
            'remaining' => $this->calculator->remaining($source),
            'packages' => CoursePackage::where('status', 'active')->whereKeyNot($source->course_package_id)->with('courseClass')->orderBy('name')->get()
                ->filter(fn (CoursePackage $package) => PackageUpgradeCalculator::isSellable($package))->values(),
            'target' => $target,
            'mode' => $mode,
            'quantity' => $convertAll ? ($preview['quantity'] ?? null) : $quantity,
            'convertAll' => $convertAll,
            // Untuk penjelasan rumus di halaman (hitungan sebenarnya tetap di kalkulator).
            'sourceUnit' => CourseCreditDebitService::unitPrice($source),
            'targetUnit' => $target ? PackageUpgradeCalculator::targetUnitPrice($target) : 0.0,
            'preview' => $preview,
            'blockedReason' => $error,
            'gatewayMissing' => ($preview['gateway_portion'] ?? 0) > 0 && ! $this->activeGateway(),
        ]);
    }

    public function store(Request $request, string $purchaseId): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'string'],
            'mode' => ['required', 'in:upgrade,convert'],
            'all' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $source = $this->ownedPurchase($request, $purchaseId);
        $target = $source ? $this->activePackage($validated['package'], $source) : null;

        if (! $source || ! $target) {
            return redirect()->route('inayule.index')->with('status', 'Paket tidak ditemukan atau sudah tidak aktif.');
        }

        $convertAll = $validated['mode'] === 'convert' && ! empty($validated['all']);
        $quantity = $validated['mode'] === 'convert' && ! $convertAll ? (int) ($validated['quantity'] ?? 0) : null;

        if ($validated['mode'] === 'convert' && ! $convertAll && $quantity < 1) {
            return back()->with('error', 'Isi jumlah credit yang mau diambil.');
        }

        $back = redirect()->route('inayule.upgrade.show', array_filter([
            'purchaseId' => $source->id,
            'package' => $target->id,
            'mode' => $validated['mode'],
            'quantity' => $quantity,
            'all' => $convertAll ? 1 : null,
        ]));

        if ($reason = $this->calculator->blockedReason($source)) {
            return $back->with('error', $reason);
        }

        try {
            // Preview hanya untuk memilih jalur; kebenarannya dicek ulang di dalam lock.
            $preview = $this->calculator->preview($user, $source, $target, $quantity, $convertAll);

            if ($preview['mode'] === 'upgrade' && $preview['gateway_portion'] > 0.0) {
                return $this->initiateGatewayCheckout($user, $source, $target, $back);
            }

            $payment = $this->calculator->completeInstant($user, $source->student, $source, $target, $quantity, $convertAll);
        } catch (UpgradeConfirmationFailedException $e) {
            return $back->with('error', self::FAILURE_MESSAGES[$e->getMessage()] ?? $e->getMessage());
        }

        (new CoursePackagePurchaseNotifier())->notify($payment);

        return redirect()->route('inayule.index')->with('success', $preview['mode'] === 'convert'
            ? 'Convert berhasil, '.rtrim(rtrim(number_format((float) $payment->credits_granted, 2, ',', '.'), '0'), ',')." credit \"{$target->name}\" sudah masuk."
            : "Upgrade ke paket \"{$target->name}\" berhasil, credit Anda sudah diperbarui.");
    }

    /** Upgrade yang masih kurang setelah trade-in + saldo. Credit & saldo belum disentuh di sini. */
    private function initiateGatewayCheckout(mixed $user, CoursePackagePurchase $source, CoursePackage $target, RedirectResponse $back): RedirectResponse
    {
        $activeGateway = $this->activeGateway();

        if (! $activeGateway) {
            return $back->with('error', 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.');
        }

        try {
            $payment = $this->calculator->beginGatewayUpgrade($source->student, $source, $target, $user, [
                'payment_gateway_id' => $activeGateway->id,
                'gateway' => $activeGateway->gateway,
                'expires_at' => now()->addMinutes($activeGateway->expiry_minutes ?? 60),
            ]);
        } catch (UpgradeConfirmationFailedException $e) {
            return $back->with('error', self::FAILURE_MESSAGES[$e->getMessage()] ?? $e->getMessage());
        }

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

            return $back->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    /** Baris pembelian milik student yang login saja. */
    private function ownedPurchase(Request $request, string $purchaseId): ?CoursePackagePurchase
    {
        $student = Student::where('user_id', $request->user()->id)->first();

        return $student
            ? CoursePackagePurchase::where('student_id', $student->id)
                ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
                ->with('student')
                ->find($purchaseId)
            : null;
    }

    private function activePackage(string $packageId, CoursePackagePurchase $source): ?CoursePackage
    {
        $package = CoursePackage::where('status', 'active')->whereKeyNot($source->course_package_id)->find($packageId);

        return $package && PackageUpgradeCalculator::isSellable($package) ? $package : null;
    }

    private function activeGateway(): ?PaymentGateway
    {
        return PaymentGateway::where('is_active', true)->where('status', 'active')->latest('updated_at')->first();
    }
}
