<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentGatewayController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = PaymentGateway::query()
            ->where('user_id', (string) $userId)
            ->with('user')
            ->orderByDesc('is_active')
            ->orderBy('gateway')
            ->paginate(10)
            ->withQueryString();

        $credentialFields = PaymentGateway::credentialFields();

        return view('settings.payment-gateway.index', compact('data', 'credentialFields'));
    }

    public function create()
    {
        $credentialFields = PaymentGateway::credentialFields();
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.payment-gateway.create', compact('credentialFields', 'adminUsers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateGateway($request);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Pemilik gateway biasanya = admin yang sedang login (yang bikin
        // credential-nya), TAPI bisa dioverride lewat dropdown "Pemilik
        // (Admin)" -- dibutuhkan karena FormPaymentController::init() nyari
        // gateway aktif berdasarkan user_id PEMBUAT FORM, bukan pemilik
        // gateway secara umum. Kalau form dibuat admin lain, gateway ini
        // harus di-assign ke admin itu supaya kedetect.
        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers($targetUserId);
        }

        PaymentGateway::create($validated);

        return redirect()
            ->route('settings.payment-gateway.index')
            ->with('success', 'Payment gateway berhasil disimpan.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(PaymentGateway::class, $id, (string) $userId);
        $credentialFields = PaymentGateway::credentialFields();
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.payment-gateway.edit', compact('data', 'credentialFields', 'adminUsers'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(PaymentGateway::class, $id, (string) $userId);

        $validated = $this->validateGateway($request);

        // Sama seperti store() -- lihat komentar di sana. Catatan penting:
        // begitu di-reassign ke admin lain, gateway ini TIDAK AKAN muncul
        // lagi di halaman index() admin yang sekarang login (index() masih
        // di-scope ke user_id sendiri), jadi cuma admin baru itu yang bisa
        // lihat/edit lagi selanjutnya. Kalau butuh dua admin sama-sama bisa
        // kelola, pertimbangkan assign ke 1 akun superadmin/bersama, bukan
        // ke akun personal.
        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers($targetUserId, $id);
        }

        AdminCrud::update(PaymentGateway::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('settings.payment-gateway.index')
            ->with('success', 'Payment gateway berhasil diupdate.');
    }

    /**
     * Jadikan gateway ini yang aktif (tanpa perlu buka form edit).
     */
    public function activate(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(PaymentGateway::class, $id, (string) $userId);

        $this->deactivateOthers((string) $userId, $id);

        AdminCrud::update(PaymentGateway::class, $id, ['is_active' => true], (string) $userId);

        return redirect()
            ->route('settings.payment-gateway.index')
            ->with('success', 'Payment gateway berhasil diaktifkan.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(PaymentGateway::class, $id, (string) $userId);

        return redirect()
            ->route('settings.payment-gateway.index')
            ->with('success', 'Payment gateway berhasil dihapus.');
    }

    /**
     * Validasi field umum + kredensial dinamis sesuai gateway yang dipilih.
     */
    private function validateGateway(Request $request): array
    {
        $validated = $request->validate([
            'gateway' => 'required|in:duitku,midtrans,ipaymu',
            'environment' => 'required|in:sandbox,production',
            'status' => 'nullable|in:active,inactive',
            'is_active' => 'nullable|boolean',
            'expiry_minutes' => 'nullable|integer|min:5|max:1440',
            // Nullable: default-nya tetap admin yang sedang login (lihat
            // store()/update()) kalau field ini tidak dikirim/dikosongkan.
            'owner_user_id' => 'nullable|string|exists:users,id',
        ]);

        $fields = PaymentGateway::credentialFields()[$validated['gateway']];

        $credentials = [];
        foreach (array_keys($fields) as $fieldKey) {
            $credentials[$fieldKey] = (string) $request->input("credentials.$fieldKey");
        }

        $request->validate(
            collect($fields)->keys()->mapWithKeys(fn ($key) => ["credentials.$key" => 'required|string|max:255'])->all()
        );

        return [
            'gateway' => $validated['gateway'],
            'environment' => $validated['environment'],
            'credentials' => $credentials,
            'is_active' => $request->boolean('is_active'),
            'status' => $validated['status'] ?? 'active',
            // Dipakai untuk hitung form_payments.expires_at (lihat FormPaymentController::init())
            // dan diteruskan ke Duitku sebagai expiryPeriod. Default 60 menit kalau admin
            // mengosongkan field-nya, sama seperti nilai lama yang dulu hardcode di kode.
            'expiry_minutes' => $validated['expiry_minutes'] ?? 60,
            'owner_user_id' => $validated['owner_user_id'] ?? null,
        ];
    }

    /**
     * Pastikan cuma 1 gateway yang is_active = true per user.
     */
    private function deactivateOthers(string $userId, ?string $exceptId = null): void
    {
        PaymentGateway::where('user_id', $userId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }
}
