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

        // BUGFIX (per keputusan owner): gateway sekarang system-wide (dipakai
        // SEMUA form apapun admin pembuatnya -- lihat WhatsappMessenger::send()),
        // jadi index() & manajemennya (edit/activate/destroy di bawah) juga
        // TIDAK di-scope lagi ke user_id sendiri. Siapapun yang punya izin
        // buka halaman Settings > WhatsApp Gateway ini (permission gate-nya
        // ada di routes/web.php) sekarang bisa lihat & kelola semua gateway,
        // bukan cuma yang dia buat sendiri -- supaya kalau token/secret perlu
        // diperbarui, tidak harus menunggu admin yang pertama kali setting.
        $data = WhatsappGateway::query()
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

        // "Pemilik (Admin)" sekarang murni catatan/kontak teknis (siapa yang
        // pegang device Konexa/Teleios-nya) -- TIDAK lagi menentukan form mana
        // yang bisa pakai gateway ini (lihat WhatsappMessenger::send(), sudah
        // system-wide). Default tetap admin yang login kalau tidak dipilih.
        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers();
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

        // Tidak lagi di-scope ke user_id sendiri (lihat catatan di index()).
        $data = AdminCrud::findOrFail(WhatsappGateway::class, $id);
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

        AdminCrud::findOrFail(WhatsappGateway::class, $id);

        $validated = $this->validateGateway($request);

        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers($id);
        }

        AdminCrud::update(WhatsappGateway::class, $id, $validated);

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

        AdminCrud::findOrFail(WhatsappGateway::class, $id);

        $this->deactivateOthers($id);

        AdminCrud::update(WhatsappGateway::class, $id, ['is_active' => true]);

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

        AdminCrud::delete(WhatsappGateway::class, $id);

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
     * Pastikan cuma 1 gateway yang is_active = true, SYSTEM-WIDE (bukan lagi
     * per user) -- lihat WhatsappMessenger::send() yang sekarang cuma nyari
     * 1 gateway aktif tanpa filter user_id sama sekali. Kalau ini tetap
     * di-scope per user seperti dulu, bisa ada lebih dari 1 baris
     * is_active=true sekaligus (punya admin A dan admin B), dan
     * WhatsappMessenger::send() jadi ambigu pilih yang mana.
     */
    private function deactivateOthers(?string $exceptId = null): void
    {
        WhatsappGateway::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }
}
