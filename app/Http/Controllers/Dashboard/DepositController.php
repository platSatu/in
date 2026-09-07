<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositPayment;
use App\Models\PaymentGateway;
use App\Services\DepositPayment\DepositPaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * "+ Tambah Saldo" di dropdown profile (resources/views/layouts/partials/
 * header.blade.php): topup saldo akun sendiri lewat payment gateway yang
 * sedang aktif (Settings > Payment Gateway, sama persis sumbernya dengan
 * form_payments).
 *
 * PENTING -- perubahan perilaku dari versi sebelumnya: store() di sini
 * DULU langsung mengkredit saldo seketika (status 'success' instan, tanpa
 * pembayaran nyata sama sekali). Itu SEBUAH LUBANG KEAMANAN karena cuma
 * butuh 'auth' (siapa pun yang login bisa menambah saldonya sendiri hanya
 * dengan mengetik angka). Sekarang store() cuma membuat sesi checkout ke
 * gateway (DepositPayment, status 'pending') -- saldo (Deposit +
 * Transaction) HANYA dibuat oleh DepositWebhookController setelah gateway
 * mengkonfirmasi pembayaran itu benar-benar diterima, tidak pernah dari
 * request browser/redirect biasa. Lihat juga
 * App\Services\DepositPayment\* (jalur terpisah dari form_payments,
 * keputusan owner supaya tidak menyentuh alur pembayaran Form/Quiz yang
 * sudah live).
 */
class DepositController extends Controller
{
    public function create(): View
    {
        if (!Auth::check()) {
            abort(401);
        }

        return view('dashboard.deposit.create', [
            'currentBalance' => Deposit::currentBalanceFor((string) Auth::id()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            abort(401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000',
        ]);

        $user = Auth::user();

        // Gateway berlaku system-wide (bukan di-scope per user_id) -- sama
        // persis keputusan yang sudah dipakai FormPaymentController::init(),
        // supaya konsisten: 1 gateway aktif dipakai untuk SEMUA transaksi
        // pembayaran di aplikasi ini, form maupun topup saldo.
        $gateway = PaymentGateway::where('is_active', true)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (!$gateway) {
            return back()->withInput()->with('error', 'Payment gateway belum diaktifkan oleh admin. Silakan hubungi penyelenggara.');
        }

        $payment = DepositPayment::create([
            'user_id' => (string) $user->id,
            'payment_gateway_id' => $gateway->id,
            'order_id' => $this->generateOrderId(),
            'gateway' => $gateway->gateway,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'handphone' => $user->handphone,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'expires_at' => now()->addMinutes($gateway->expiry_minutes ?? 60),
        ]);

        try {
            $driver = DepositPaymentGatewayFactory::make($gateway);

            if ($driver->requiresMethodSelection()) {
                return redirect()->route('dashboard.deposit.select-method', ['order_id' => $payment->order_id]);
            }

            $result = $driver->createTransaction($payment);

            $payment->update([
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            if (empty($result['redirect_url'])) {
                throw new \RuntimeException('Gateway tidak mengembalikan URL pembayaran.');
            }

            return redirect()->away($result['redirect_url']);
        } catch (Throwable $e) {
            Log::error('[DEPOSIT] Gagal init transaksi topup saldo', [
                'order_id' => $payment->order_id,
                'gateway' => $gateway->gateway,
                'message' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);

            return back()->withInput()->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    /**
     * Khusus Duitku: tampilkan daftar metode pembayaran untuk dipilih user
     * sebelum transaksi benar-benar dibuat (lihat docblock
     * DepositDuitkuGateway).
     */
    public function selectMethodForm(string $orderId): View
    {
        $payment = DepositPayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $gateway = $payment->paymentGateway;

        if (!$gateway || $gateway->gateway !== 'duitku') {
            abort(404);
        }

        try {
            $methods = DepositPaymentGatewayFactory::make($gateway)->getPaymentMethods($payment);
        } catch (Throwable $e) {
            Log::error('[DEPOSIT][Duitku] Gagal ambil daftar metode pembayaran', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return view('dashboard.deposit.select-method', [
                'payment' => $payment,
                'methods' => [],
                'error' => 'Gagal mengambil daftar metode pembayaran Duitku, silakan coba lagi.',
            ]);
        }

        return view('dashboard.deposit.select-method', compact('payment', 'methods'));
    }

    public function selectMethod(Request $request, string $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:10'],
        ]);

        $payment = DepositPayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $gateway = $payment->paymentGateway;

        if (!$gateway || $gateway->gateway !== 'duitku') {
            abort(422);
        }

        try {
            $driver = DepositPaymentGatewayFactory::make($gateway);
            $result = $driver->createTransaction($payment, $validated['payment_method']);

            $payment->update([
                'payment_method' => $validated['payment_method'],
                'payment_url' => $result['redirect_url'] ?? null,
                'gateway_reference' => $result['reference'] ?? null,
                'raw_response' => $result['raw'] ?? null,
            ]);

            if (empty($result['redirect_url'])) {
                throw new \RuntimeException('Gateway tidak mengembalikan URL pembayaran.');
            }

            return redirect()->away($result['redirect_url']);
        } catch (Throwable $e) {
            Log::error('[DEPOSIT][Duitku] Gagal buat transaksi setelah pilih metode', [
                'order_id' => $payment->order_id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat transaksi pembayaran, silakan coba lagi.');
        }
    }

    /**
     * Halaman transit setelah user kembali dari gateway -- murni UX (bukan
     * sumber kebenaran status), cuma menampilkan status terkini & polling
     * lewat status() di bawah. Saldo tetap hanya berubah lewat webhook.
     */
    public function return(Request $request): View
    {
        $orderId = (string) $request->query('order_id', '');

        $payment = $orderId !== ''
            ? DepositPayment::where('order_id', $orderId)->where('user_id', (string) Auth::id())->first()
            : null;

        return view('dashboard.deposit.return', compact('payment', 'orderId'));
    }

    /**
     * Dipoll halaman return() tiap beberapa detik. Self-heal transaksi yang
     * masih 'pending' tapi sudah lewat expires_at -- pola sama persis
     * dengan FormPaymentController::status().
     */
    public function status(string $orderId): JsonResponse
    {
        $payment = DepositPayment::where('order_id', $orderId)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        if ($payment->status === 'pending' && $payment->expires_at && now()->greaterThanOrEqualTo($payment->expires_at)) {
            $expired = DepositPayment::where('id', $payment->id)
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
            'balance' => Deposit::currentBalanceFor((string) Auth::id()),
        ]);
    }

    private function generateOrderId(): string
    {
        do {
            $orderId = 'DEP' . now()->format('ymd') . strtoupper(Str::random(8));
        } while (DepositPayment::where('order_id', $orderId)->exists());

        return $orderId;
    }
}
