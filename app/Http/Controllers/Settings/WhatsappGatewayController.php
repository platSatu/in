<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
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
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('settings.whatsapp-gateway.index', compact('data'));
    }

    public function create()
    {
        $gatewayOptions = WhatsappGateway::gatewayOptions();

        return view('settings.whatsapp-gateway.create', compact('gatewayOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateGateway($request);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        if ($validated['is_active']) {
            $this->deactivateOthers((string) $userId);
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

        return view('settings.whatsapp-gateway.edit', compact('data', 'gatewayOptions'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(WhatsappGateway::class, $id, (string) $userId);

        $validated = $this->validateGateway($request);

        if ($validated['is_active']) {
            $this->deactivateOthers((string) $userId, $id);
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
