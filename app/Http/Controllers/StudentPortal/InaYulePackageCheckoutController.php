<?php

namespace App\Http\Controllers\StudentPortal;

use App\Http\Controllers\Controller;
use App\Models\CourseCredit;
use App\Models\CoursePackage;
use App\Models\CoursePackagePayment;
use App\Models\CoursePackagePurchase;
use App\Models\Deposit;
use App\Models\PaymentGateway;
use App\Models\Student;
use App\Models\Transaction;
use App\Services\CoursePackagePayment\CoursePackagePaymentGatewayFactory;
use App\Services\CoursePackagePayment\CoursePackagePurchaseNotifier;
use App\Services\StudentIdentityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Checkout pembelian package BERBAYAR (effectivePrice() > 0) -- package
 * trial gratis TETAP lewat InaYulePackageController::claimTrial(), tidak
 * disentuh sama sekali oleh controller ini.
 *
 * ALUR "MIX OTOMATIS" (permintaan user, 16 September 2026): harga package
 * ditutup saldo Deposit dulu sebisa mungkin (`deposit_portion`), SISANYA baru
 * ditagih ke payment gateway (`gateway_portion`) -- tidak ada pilihan manual
 * di UI, murni dihitung server.
 *
 * DUA JALUR PENYELESAIAN, tergantung hasil split (dihitung ULANG di server
 * setiap kali, TIDAK PERNAH percaya angka dari browser):
 *
 * 1. gateway_portion = 0 (100% tertutup saldo Deposit) -- tidak ada apa pun
 *    yang perlu dikonfirmasi pihak ketiga, jadi diproses INSTAN di
 *    completeWithDepositOnly(): 1 DB transaction dengan lockForUpdate() di
 *    baris Student, users, Deposit terakhir, & CourseCredit terakhir (pola
 *    sama dengan InaYulePackageController::claimTrial() &
 *    DepositWebhookController), balance Deposit di-debit & CourseCredit
 *    di-kredit SEKALIGUS, baris CoursePackagePayment langsung dibuat
 *    berstatus 'paid' (murni buat riwayat, tidak pernah 'pending').
 *
 * 2. gateway_portion > 0 (murni gateway ATAU campuran) -- SESUAI prinsip
 *    keamanan yang sudah dipakai DepositController/DepositWebhookController
 *    ("saldo/credit TIDAK PERNAH dikreditkan dari request browser, HANYA
 *    dari webhook server-to-server yang sudah diverifikasi signature-nya"):
 *    initiateGatewayCheckout() di sini CUMA membuat baris CoursePackagePayment
 *    'pending' + redirect ke gateway. Deposit BELUM disentuh sama sekali di
 *    sini walau deposit_portion > 0 (dicatat sebagai ANGKA RENCANA saja) --
 *    supaya tidak ada kondisi "saldo sudah kepotong tapi gateway gagal/
 *    dibatalkan user". Pemotongan Deposit + pembuatan CoursePackagePurchase +
 *    CourseCredit yang SEBENARNYA baru terjadi di
 *    InaYulePackageWebhookController setelah gateway konfirmasi 'paid', DAN
 *    baru setelah saldo Deposit di-re-check ulang saat itu juga (lihat
 *    docblock webhook -- bisa saja berubah selama user di halaman gateway).
 */
class InaYulePackageCheckoutController extends Controller
{
    public function show(Request $request, string $packageId): View|RedirectResponse
    {
        $user = $request->user();
        $package = CoursePackage::where('status', 'active')->find($packageId);

        if (!$package) {
            return redirect()->route('inayule.index')->with('status', 'Package tidak ditemukan atau sudah tidak aktif.');
        }

        $price = $package->effectivePrice();

        if ($price <= 0.0) {
            return redirect()->route('inayule.index')->with('status', 'Package ini gratis (trial) -- klaim lewat tombol "Klaim Gratis", bukan lewat checkout.');
        }

        $depositBalance = Deposit::currentBalanceFor((string) $user->id);
        $depositPortion = min($depositBalance, $price);
        $gatewayPortion = max($price - $depositPortion, 0.0);

        $activeGateway = $gatewayPortion > 0
            ? PaymentGateway::where('is_active', true)->where('status', 'active')->latest('updated_at')->first()
            : null;

        return view('student-portal.inayule.checkout', [
            'package' => $package,
            'price' => $price,
            'depositBalance' => $depositBalance,
            'depositPortion' => $depositPortion,
            'gatewayPortion' => $gatewayPortion,
            'gatewayMissing' => $gatewayPortion > 0 && !$activeGateway,
        ]);
    }

    public function store(Request $request, string $packageId): RedirectResponse
    {
        $user = $request->user();

        // Sama seperti claimTrial() -- jaring pengaman untuk User lama yang
        // belum sempat ke-link ke Student.
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            $student = (new StudentIdentityResolver())->findOrCreate([
                'name' => $user->name,
                'email' => $user->email,
                'handphone' => $user->handphone,
            ]);

            if (empty($student->user_id)) {
                $student->user_id = $user->id;
                $student->save();
            }
        }

        $package = CoursePackage::where('status', 'active')->find($packageId);

        if (!$package) {
            return redirect()->route('inayule.index')->with('status', 'Package tidak ditemukan atau sudah tidak aktif.');
        }

        // Pengamanan utama: harga DIHITUNG ULANG di server, TIDAK PERNAH
        // percaya input apa pun dari request untuk angka ini.
        $price = $package->effectivePrice();

        if ($price <= 0.0) {
            return redirect()->route('inayule.index')->with('status', 'Package ini gratis (trial) -- klaim lewat tombol "Klaim Gratis", bukan lewat checkout.');
        }

        // Preview (belum di-lock) -- cuma untuk MEMILIH jalur mana yang
        // dipakai. Kebenaran akhirnya tetap di-cek ULANG di dalam lock pada
        // completeWithDepositOnly(), atau di webhook untuk jalur gateway.
        $depositBalancePreview = Deposit::currentBalanceFor((string) $user->id);
        $gatewayPortionPreview = max($price - min($depositBalancePreview, $price), 0.0);

        if ($gatewayPortionPreview <= 0.0) {
            $outcome = $this->completeWithDepositOnly($user, $student, $package, $price);

            if ($outcome['ok']) {
                (new CoursePackagePurchaseNotifier())->notify($outcome['payment']);

                return redirect()->route('inayule.index')->with('success', 'Package "' . $package->name . '" berhasil dibeli pakai saldo, credit Anda bertambah.');
            }

            // Saldo berubah tepat di antara preview & lock (race jarang) --
            // paling aman minta user coba lagi (submit ulang akan otomatis
            // dihitung ulang, bisa jadi jatuh ke jalur gateway kalau saldo
            // ternyata sudah berkurang).
            return redirect()->route('inayule.checkout.show', ['packageId' => $package->id])
                ->with('error', 'Saldo Anda baru saja berubah, silakan coba lagi.');
        }

        return $this->initiateGatewayCheckout($user, $student, $package, $price, $depositBalancePreview);
    }

    /**
     * Jalur instan -- lihat docblock class di atas (poin 1). Dibungkus 1 DB
     * transaction dengan row locking supaya tidak mungkin ada 2 pembelian
     * beruntun dari saldo yang sama lolos berdua-duanya (double spending).
     *
     * @return array{ok: bool, payment?: CoursePackagePayment}
     */
    private function completeWithDepositOnly(mixed $user, Student $student, CoursePackage $package, float $price): array
    {
        return DB::transaction(function () use ($user, $student, $package, $price): array {
            $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();

            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();

            $lastDeposit = Deposit::where('user_id', $user->id)
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $depositBalanceBefore = (float) ($lastDeposit?->balance ?? 0);

            // Re-cek DI DALAM lock -- kalau ternyata sudah tidak cukup
            // (race dengan pembelian lain yang barusan commit duluan),
            // batalkan jalur ini, jangan sampai ada Deposit debit "minus".
            if ($depositBalanceBefore < $price) {
                return ['ok' => false];
            }

            $depositBalanceAfter = $depositBalanceBefore - $price;

            $orderId = $this->generateOrderId();

            $deposit = Deposit::create([
                'user_id' => (string) $user->id,
                'debit' => $price,
                'kredit' => 0,
                'balance' => $depositBalanceAfter,
                'description' => "Beli package: {$package->name} (order {$orderId})",
                'payment_status' => 'success',
                'payment_method' => 'saldo',
                'payment_date' => now(),
            ]);

            Transaction::create([
                'transaction_code' => 'TRX-' . now()->format('YmdHisv') . '-' . strtoupper(Str::random(6)),
                'user_id' => (string) $user->id,
                'type' => 'debit',
                'amount' => $price,
                'balance_before' => $depositBalanceBefore,
                'balance_after' => $depositBalanceAfter,
                'description' => "Beli package: {$package->name}",
                'reference_type' => 'course_package_payment',
                'reference_id' => $orderId,
                'status' => 'success',
                'channel' => 'saldo',
                'metadata' => [
                    'order_id' => $orderId,
                    'course_package_id' => $package->id,
                    'source' => 'inayule.checkout.deposit-only',
                ],
                'created_by' => (string) $user->id,
                'transaction_date' => now(),
            ]);

            $purchase = CoursePackagePurchase::create([
                'student_id' => $lockedStudent->id,
                'course_package_id' => $package->id,
                'price_paid' => $price,
                'credits_granted' => $package->credits,
                'source' => CoursePackagePurchase::SOURCE_DEPOSIT_PURCHASE,
                'status' => CoursePackagePurchase::STATUS_COMPLETED,
            ]);

            $newCreditBalance = CourseCredit::currentBalanceFor($lockedStudent->id) + (float) $package->credits;

            CourseCredit::create([
                'student_id' => $lockedStudent->id,
                'course_package_purchase_id' => $purchase->id,
                'debit' => 0,
                'kredit' => $package->credits,
                'balance' => $newCreditBalance,
                'description' => 'Beli package: ' . $package->name,
            ]);

            $payment = CoursePackagePayment::create([
                'student_id' => $lockedStudent->id,
                'user_id' => (string) $user->id,
                'course_package_id' => $package->id,
                'course_package_purchase_id' => $purchase->id,
                'order_id' => $orderId,
                'gateway' => null,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'handphone' => $user->handphone,
                'price_total' => $price,
                'deposit_portion' => $price,
                'gateway_portion' => 0,
                'credits_granted' => $package->credits,
                'status' => CoursePackagePayment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            return ['ok' => true, 'payment' => $payment];
        });
    }

    /**
     * Jalur gateway (murni gateway ATAU campuran) -- lihat docblock class di
     * atas (poin 2). TIDAK menyentuh Deposit sama sekali di sini.
     */
    private function initiateGatewayCheckout(mixed $user, Student $student, CoursePackage $package, float $price, float $depositBalance): RedirectResponse
    {
        $activeGateway = PaymentGateway::where('is_active', true)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (!$activeGateway) {
            return redirect()->route('inayule.checkout.show', ['packageId' => $package->id])
                ->with('error', 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.');
        }

        $depositPortion = min($depositBalance, $price);
        $gatewayPortion = max($price - $depositPortion, 0.0);

        $payment = CoursePackagePayment::create([
            'student_id' => $student->id,
            'user_id' => (string) $user->id,
            'course_package_id' => $package->id,
            'payment_gateway_id' => $activeGateway->id,
            'order_id' => $this->generateOrderId(),
            'gateway' => $activeGateway->gateway,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'handphone' => $user->handphone,
            'price_total' => $price,
            'deposit_portion' => $depositPortion,
            'gateway_portion' => $gatewayPortion,
            'credits_granted' => $package->credits,
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
            Log::error('[COURSE-PACKAGE] Gagal init transaksi checkout package', [
                'order_id' => $payment->order_id,
                'gateway' => $activeGateway->gateway,
                'message' => $e->getMessage(),
            ]);

            $payment->update(['status' => CoursePackagePayment::STATUS_FAILED]);

            return redirect()->route('inayule.checkout.show', ['packageId' => $package->id])
                ->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    /**
     * Khusus Duitku -- lihat docblock DepositController::selectMethodForm(),
     * pola sama persis.
     */
    public function selectMethodForm(string $orderId): View
    {
        $payment = CoursePackagePayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->where('status', CoursePackagePayment::STATUS_PENDING)
            ->firstOrFail();

        $gateway = $payment->paymentGateway;

        if (!$gateway || $gateway->gateway !== 'duitku') {
            abort(404);
        }

        try {
            $methods = CoursePackagePaymentGatewayFactory::make($gateway)->getPaymentMethods($payment);
        } catch (Throwable $e) {
            Log::error('[COURSE-PACKAGE][Duitku] Gagal ambil daftar metode pembayaran', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return view('student-portal.inayule.checkout-select-method', [
                'payment' => $payment,
                'methods' => [],
                'error' => 'Gagal mengambil daftar metode pembayaran Duitku, silakan coba lagi.',
            ]);
        }

        return view('student-portal.inayule.checkout-select-method', compact('payment', 'methods'));
    }

    public function selectMethod(Request $request, string $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:10'],
        ]);

        $payment = CoursePackagePayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->where('status', CoursePackagePayment::STATUS_PENDING)
            ->firstOrFail();

        $gateway = $payment->paymentGateway;

        if (!$gateway || $gateway->gateway !== 'duitku') {
            abort(422);
        }

        try {
            $driver = CoursePackagePaymentGatewayFactory::make($gateway);
            $result = $driver->createTransaction($payment, $validated['payment_method']);

            $payment->update([
                'payment_method' => $validated['payment_method'],
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            if (empty($result['redirect_url'])) {
                throw new RuntimeException('Gateway tidak mengembalikan URL pembayaran.');
            }

            return redirect()->away($result['redirect_url']);
        } catch (Throwable $e) {
            Log::error('[COURSE-PACKAGE][Duitku] Gagal buat transaksi setelah pilih metode', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    /**
     * Halaman transit setelah user kembali dari gateway -- murni UX, saldo &
     * credit tetap hanya berubah lewat webhook. Lihat docblock
     * DepositController::return().
     */
    public function return(Request $request): View
    {
        $orderId = (string) $request->query('order_id', '');

        $payment = $orderId !== ''
            ? CoursePackagePayment::where('order_id', $orderId)->where('user_id', (string) Auth::id())->with('coursePackage')->first()
            : null;

        return view('student-portal.inayule.checkout-return', compact('payment', 'orderId'));
    }

    /**
     * Dipoll halaman return() tiap beberapa detik -- self-heal transaksi
     * pending yang sudah lewat expires_at, pola sama persis dengan
     * DepositController::status().
     */
    public function status(string $orderId): JsonResponse
    {
        $payment = CoursePackagePayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        if ($payment->status === CoursePackagePayment::STATUS_PENDING && $payment->expires_at && now()->greaterThanOrEqualTo($payment->expires_at)) {
            $expired = CoursePackagePayment::where('id', $payment->id)
                ->where('status', CoursePackagePayment::STATUS_PENDING)
                ->update(['status' => CoursePackagePayment::STATUS_EXPIRED]);

            if ($expired === 1) {
                $payment->status = CoursePackagePayment::STATUS_EXPIRED;
            }
        }

        return response()->json([
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'expires_at' => optional($payment->expires_at)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'credit_balance' => CourseCredit::currentBalanceFor($payment->student_id),
        ]);
    }

    private function generateOrderId(): string
    {
        do {
            $orderId = 'CPP' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (CoursePackagePayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
