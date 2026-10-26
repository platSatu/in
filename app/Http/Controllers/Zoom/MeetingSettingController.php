<?php

namespace App\Http\Controllers\Zoom;

use App\Http\Controllers\Controller;
use App\Models\ZoomSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tab "Pengaturan Zoom" di menu Zoom > Kelola Meeting: kelola akun Zoom
 * (Account ID, Client ID, Client Secret, Secret Token -- disimpan ter-enkripsi
 * di tabel zoom_settings) & switch akun mana yang dipakai saat membuat
 * meeting baru. Cuma 1 akun yang boleh aktif dalam satu waktu (sama dengan
 * aturan di Settings > Setting Zoom -- tabelnya juga sama).
 *
 * Semua aksi kembali ke zoom.meeting.index?tab=settings.
 */
class MeetingSettingController extends Controller
{
    private const SECRET_FIELDS = ['account_id', 'client_id', 'client_secret', 'secret_token'];

    public function store(Request $request)
    {
        $userId = Auth::id() ?? abort(401);

        $validated = $request->validateWithBag('zoomSetting', [
            'name' => 'nullable|string|max:255',
            'account_id' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'secret_token' => 'nullable|string|max:255',
        ]);

        $makeActive = $request->boolean('is_active');

        DB::transaction(function () use ($validated, $makeActive, $userId) {
            if ($makeActive) {
                $this->deactivateOthers();
            }

            ZoomSetting::create($validated + [
                'user_id' => (string) $userId,
                'is_active' => $makeActive,
                'status' => 'active',
            ]);
        });

        return $this->back('Akun Zoom berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $setting = ZoomSetting::findOrFail($id);

        $validated = $request->validateWithBag('zoomSetting', [
            'name' => 'nullable|string|max:255',
            'account_id' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'secret_token' => 'nullable|string|max:255',
        ]);

        // Form edit tidak menampilkan ulang kredensial lama (demi keamanan),
        // jadi field yang dikosongkan berarti "tidak diubah".
        foreach (self::SECRET_FIELDS as $field) {
            if (($validated[$field] ?? '') === '') {
                unset($validated[$field]);
            }
        }

        $setting->update($validated);

        // Kredensial bisa berubah -- buang access token lama yang di-cache
        // App\Services\Zoom\ZoomClient untuk akun ini.
        Cache::forget("zoom_access_token:{$setting->id}");

        return $this->back('Akun Zoom berhasil diupdate.');
    }

    /**
     * Switch aktif/nonaktif. Menyalakan satu akun otomatis mematikan akun
     * lain -- akun yang menyala inilah yang dipakai saat "Buat Meeting".
     */
    public function toggle(Request $request, string $id)
    {
        $setting = ZoomSetting::findOrFail($id);
        $turnOn = $request->boolean('is_active');

        DB::transaction(function () use ($setting, $turnOn) {
            if ($turnOn) {
                $this->deactivateOthers($setting->id);
            }

            $setting->update([
                'is_active' => $turnOn,
                // activeSetting() juga mensyaratkan status=active.
                'status' => $turnOn ? 'active' : $setting->status,
            ]);
        });

        return $this->back($turnOn
            ? "Akun \"" . ($setting->name ?? 'Zoom Account') . "\" sekarang dipakai untuk membuat meeting baru."
            : "Akun \"" . ($setting->name ?? 'Zoom Account') . "\" dinonaktifkan. Belum ada akun aktif untuk membuat meeting baru.");
    }

    public function destroy(string $id)
    {
        $setting = ZoomSetting::findOrFail($id);

        Cache::forget("zoom_access_token:{$setting->id}");
        $setting->delete();

        return $this->back('Akun Zoom berhasil dihapus.');
    }

    private function deactivateOthers(?string $exceptId = null): void
    {
        ZoomSetting::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_active' => false]);
    }

    private function back(string $message)
    {
        return redirect()
            ->route('zoom.meeting.index', ['tab' => 'settings'])
            ->with('success', $message);
    }
}
