<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappGatewayController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = WhatsappGateway::query()
            ->where('user_id', (string) $userId)
            ->with('user')
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('settings.whatsapp-gateway.index', compact('data'));
    }

    public function create()
    {
        $gatewayOptions = WhatsappGateway::gatewayOptions();
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.whatsapp-gateway.create', compact('gatewayOptions', 'adminUsers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateGateway($request);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        // Sama seperti Payment Gateway (lihat PaymentGatewayController) -- WA
        // dikirim lewat WhatsappMessenger::send() yang nyari gateway aktif
        // berdasarkan user_id PEMBUAT FORM, bukan admin yang login waktu
        // menyimpan Settings ini. Kalau form dibuat admin lain, gateway harus
        // di-assign ke admin itu supaya kedetect & WA-nya terkirim.
        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers($targetUserId);
        }

        WhatsappGateway::create($validated);

        return redirect()
            ->route('settings.whatsapp-gateway.index')
            ->with('success', 'WhatsApp gateway berhasil disimpan.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(WhatsappGateway::class, $id, (string) $userId);
        $gatewayOptions = WhatsappGateway::gatewayOptions();
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.whatsapp-gateway.edit', compact('data', 'gatewayOptions', 'adminUsers'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(WhatsappGateway::class, $id, (string) $userId);

        $validated = $this->validateGateway($request);

        // Lihat komentar di store(). Catatan yang sama juga berlaku: begitu
        // di-reassign ke admin lain, gateway ini tidak lagi muncul di index()
        // admin yang sekarang login.
        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers($targetUserId, $id);
        }

        AdminCrud::update(WhatsappGateway::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('settings.whatsapp-gateway.index')
            ->with('success', 'WhatsApp gateway berhasil diupdate.');
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

        AdminCrud::findOrFail(WhatsappGateway::class, $id, (string) $userId);

        $this->deactivateOthers((string) $userId, $id);

        AdminCrud::update(WhatsappGateway::class, $id, ['is_active' => true], (string) $userId);

        return redirect()
            ->route('settings.whatsapp-gateway.index')
            ->with('success', 'WhatsApp gateway berhasil diaktifkan.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(WhatsappGateway::class, $id, (string) $userId);

        return redirect()
            ->route('settings.whatsapp-gateway.index')
            ->with('success', 'WhatsApp gateway berhasil dihapus.');
    }

    /**
     * Validasi field gateway. Sengaja simpel (bukan kredensial dinamis per-provider
     * seperti payment gateway) karena semua provider yang didukung sekarang cuma
     * butuh 3 field yang sama: api_host, token, secret_key.
     */
    private function validateGateway(Request $request): array
    {
        $validated = $request->validate([
            'gateway' => 'required|in:' . implode(',', array_keys(WhatsappGateway::gatewayOptions())),
            'name' => 'nullable|string|max:255',
            'api_host' => 'required|string|max:255|url',
            'token' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'is_active' => 'nullable|boolean',
            // Nullable: default-nya tetap admin yang sedang login (lihat
            // store()/update()) kalau field ini tidak dikirim/dikosongkan.
            'owner_user_id' => 'nullable|string|exists:users,id',
        ]);

        // Host disimpan tanpa trailing slash supaya gampang disambung dengan path
        // endpoint (mis. /api/v2/send-message) saat dipakai mengirim pesan.
        $validated['api_host'] = rtrim($validated['api_host'], '/');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['status'] = $validated['status'] ?? 'active';

        return $validated;
    }

    /**
     * Pastikan cuma 1 gateway yang is_active = true per user.
     */
    private function deactivateOthers(string $userId, ?string $exceptId = null): void
    {
        WhatsappGateway::where('user_id', $userId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }
}
