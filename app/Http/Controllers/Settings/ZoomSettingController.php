<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ZoomSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD kredensial Server-to-Server OAuth Zoom (menu Settings > Setting
 * Zoom). Polanya sama persis dengan PaymentGatewayController/
 * WhatsappGatewayController: system-wide (tidak di-scope per user_id),
 * cuma 1 baris yang boleh is_active=true dalam satu waktu -- itu yang
 * dipakai App\Services\Zoom\ZoomClient.
 */
class ZoomSettingController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = ZoomSetting::query()
            ->with('user')
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('settings.zoom.index', compact('data'));
    }

    public function create()
    {
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.zoom.create', compact('adminUsers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSetting($request);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        if ($validated['is_active']) {
            $this->deactivateOthers();
        }

        ZoomSetting::create($validated);

        return redirect()
            ->route('settings.zoom.index')
            ->with('success', 'Konfigurasi Zoom berhasil disimpan.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(ZoomSetting::class, $id);
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('settings.zoom.edit', compact('data', 'adminUsers'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(ZoomSetting::class, $id);

        $validated = $this->validateSetting($request);

        $targetUserId = $validated['owner_user_id'] ?? (string) $userId;
        unset($validated['owner_user_id']);
        $validated['user_id'] = $targetUserId;

        // Kalau admin mengosongkan field Client Secret / Secret Token saat
        // edit (dibiarkan kosong karena tidak mau ganti), pertahankan nilai
        // lama -- form edit sengaja tidak menampilkan ulang nilai asli demi
        // keamanan (lihat resources/views/settings/zoom/edit.blade.php),
        // jadi "kosong" di sini berarti "tidak diubah", bukan "dihapus".
        $existing = AdminCrud::findOrFail(ZoomSetting::class, $id);
        foreach (['account_id', 'client_id', 'client_secret', 'secret_token'] as $secretField) {
            if ($validated[$secretField] === null || $validated[$secretField] === '') {
                $validated[$secretField] = $existing->{$secretField};
            }
        }

        if ($validated['is_active']) {
            $this->deactivateOthers($id);
        }

        AdminCrud::update(ZoomSetting::class, $id, $validated);

        return redirect()
            ->route('settings.zoom.index')
            ->with('success', 'Konfigurasi Zoom berhasil diupdate.');
    }

    /**
     * Jadikan konfigurasi ini yang aktif (tanpa perlu buka form edit).
     */
    public function activate(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(ZoomSetting::class, $id);

        $this->deactivateOthers($id);

        AdminCrud::update(ZoomSetting::class, $id, ['is_active' => true]);

        return redirect()
            ->route('settings.zoom.index')
            ->with('success', 'Konfigurasi Zoom berhasil diaktifkan.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(ZoomSetting::class, $id);

        return redirect()
            ->route('settings.zoom.index')
            ->with('success', 'Konfigurasi Zoom berhasil dihapus.');
    }

    /**
     * Validasi field setting. account_id/client_id/client_secret sengaja
     * 'nullable' di sini (bukan 'required') supaya form edit boleh
     * dikosongkan tanpa mengubah nilai lama -- lihat update() di atas.
     * store() tetap mewajibkan diisi lewat pengecekan manual di bawah.
     */
    private function validateSetting(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'account_id' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'secret_token' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'is_active' => 'nullable|boolean',
            'owner_user_id' => 'nullable|string|exists:users,id',
        ]);

        if ($request->routeIs('settings.zoom.store')) {
            $request->validate([
                'account_id' => 'required|string|max:255',
                'client_id' => 'required|string|max:255',
                'client_secret' => 'required|string|max:255',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['status'] = $validated['status'] ?? 'active';

        return $validated;
    }

    /**
     * Pastikan cuma 1 konfigurasi yang is_active=true dalam satu waktu --
     * itu satu-satunya baris yang dipakai App\Services\Zoom\ZoomClient.
     */
    private function deactivateOthers(?string $exceptId = null): void
    {
        ZoomSetting::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }
}
