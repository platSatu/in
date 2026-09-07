<?php

namespace App\Services\Zoom;

use App\Models\ZoomSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klien tipis untuk Zoom API lewat Server-to-Server OAuth (lihat
 * https://developers.zoom.us/docs/internal-apps/s2s-oauth/). Kredensial
 * diambil dari baris App\Models\ZoomSetting yang sedang aktif (menu
 * Settings > Setting Zoom) -- kalau belum ada yang di-set/diaktifkan,
 * setiap method di sini melempar RuntimeException dengan pesan yang jelas.
 *
 * Dipakai oleh App\Http\Controllers\Zoom\MeetingController untuk
 * membuat/mengubah/mengakhiri/menghapus meeting & mengambil daftar
 * recording-nya.
 */
class ZoomClient
{
    private const TOKEN_URL = 'https://zoom.us/oauth/token';

    private const API_BASE = 'https://api.zoom.us/v2';

    private ?ZoomSetting $setting;

    public function __construct(?ZoomSetting $setting = null)
    {
        $this->setting = $setting ?? ZoomSetting::activeSetting();
    }

    private function setting(): ZoomSetting
    {
        if (! $this->setting) {
            throw new RuntimeException(
                'Belum ada konfigurasi Zoom yang aktif. Silakan atur dulu di menu Settings > Setting Zoom.'
            );
        }

        return $this->setting;
    }

    /**
     * Ambil access token, di-cache per konfigurasi selama ~55 menit (token
     * asli Zoom berlaku 1 jam/3600 detik -- diberi jeda supaya tidak
     * terpakai tepat saat kedaluwarsa). Server-to-Server OAuth tidak punya
     * refresh token (lihat dokumentasi Zoom): kalau cache kosong/habis,
     * tinggal minta token baru lagi lewat grant_type=account_credentials.
     */
    public function accessToken(): string
    {
        $setting = $this->setting();

        return Cache::remember(
            "zoom_access_token:{$setting->id}",
            now()->addMinutes(55),
            function () use ($setting) {
                $response = Http::asForm()
                    ->withBasicAuth($setting->client_id, $setting->client_secret)
                    ->post(self::TOKEN_URL, [
                        'grant_type' => 'account_credentials',
                        'account_id' => $setting->account_id,
                    ]);

                if ($response->failed()) {
                    Log::error('[ZOOM] Gagal mengambil access token', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);

                    throw new RuntimeException(
                        'Gagal mengambil access token Zoom: ' .
                        ($response->json('reason') ?? $response->json('message') ?? $response->body())
                    );
                }

                $token = $response->json('access_token');

                if (! $token) {
                    throw new RuntimeException('Zoom tidak mengembalikan access_token yang valid.');
                }

                return $token;
            }
        );
    }

    private function api()
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->baseUrl(self::API_BASE);
    }

    /**
     * Buat scheduled meeting baru. $userId 'me' berarti dibuat atas nama
     * pemilik akun Zoom yang di-set di Settings > Setting Zoom.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createMeeting(array $payload, string $userId = 'me'): array
    {
        $response = $this->api()->post("/users/{$userId}/meetings", $payload);

        return $this->parse($response, 'membuat meeting');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateMeeting(string $zoomMeetingId, array $payload): void
    {
        $response = $this->api()->patch("/meetings/{$zoomMeetingId}", $payload);

        if ($response->failed()) {
            $this->parse($response, 'mengubah meeting');
        }
    }

    /**
     * @return array<string, mixed>|null null kalau meeting sudah tidak ada di Zoom (404).
     */
    public function getMeeting(string $zoomMeetingId): ?array
    {
        $response = $this->api()->get("/meetings/{$zoomMeetingId}");

        if ($response->status() === 404) {
            return null;
        }

        return $this->parse($response, 'mengambil detail meeting');
    }

    /**
     * Paksa akhiri meeting yang sedang berlangsung (host belum/lupa
     * mengakhiri sendiri sendiri) -- PUT /meetings/{id}/status, action=end.
     */
    public function endMeeting(string $zoomMeetingId): void
    {
        $response = $this->api()->put("/meetings/{$zoomMeetingId}/status", [
            'action' => 'end',
        ]);

        // Zoom membalas 404 kalau meeting memang sedang tidak berlangsung --
        // dianggap "sudah berakhir", bukan error yang perlu digagalkan.
        if ($response->failed() && $response->status() !== 404) {
            $this->parse($response, 'mengakhiri meeting');
        }
    }

    /**
     * Hapus/batalkan scheduled meeting dari Zoom.
     */
    public function deleteMeeting(string $zoomMeetingId): void
    {
        $response = $this->api()->delete("/meetings/{$zoomMeetingId}");

        // 404 berarti meeting-nya sudah tidak ada di sisi Zoom -- aman
        // dianggap berhasil (tujuan akhirnya memang supaya sudah tidak ada).
        if ($response->failed() && $response->status() !== 404) {
            $this->parse($response, 'menghapus meeting');
        }
    }

    /**
     * Daftar recording cloud untuk 1 meeting. Zoom membalas 404 kalau
     * meeting belum pernah direkam/belum selesai diproses -- dianggap
     * "belum ada recording" (null), bukan error.
     *
     * @return array<string, mixed>|null
     */
    public function listRecordings(string $zoomMeetingId): ?array
    {
        $response = $this->api()->get("/meetings/{$zoomMeetingId}/recordings");

        if ($response->status() === 404) {
            return null;
        }

        return $this->parse($response, 'mengambil rekaman meeting');
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(Response $response, string $action): array
    {
        if ($response->failed()) {
            Log::error("[ZOOM] Gagal {$action}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                "Gagal {$action} di Zoom: " .
                ($response->json('message') ?? $response->body())
            );
        }

        return $response->json() ?? [];
    }
}
