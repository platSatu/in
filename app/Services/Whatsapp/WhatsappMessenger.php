<?php

namespace App\Services\Whatsapp;

use App\Models\Form;
use App\Models\WhatsappGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dipindah dari App\Http\Controllers\FrontendController (private methods
 * buildMessageFromTemplate() & sendWhatsapp()) supaya bisa dipakai bareng oleh
 * controller lain juga — mis. FormController::saveResult() untuk kirim WA
 * setelah admin selesai input hasil manual. Perilaku persis sama seperti
 * sebelumnya, cuma dipindah lokasinya (bukan ditulis ulang logikanya).
 */
class WhatsappMessenger
{
    /**
     * Susun isi pesan WhatsApp dari template yang terpasang di form
     * ($form->whatsappTemplate). Kalau form belum punya template
     * (whatsapp_template_id null / relasi kosong), pakai format default.
     *
     * @param array $placeholders key => value, key TANPA kurung kurawal, misal 'name' untuk {{name}}
     */
    public function buildMessageFromTemplate(Form $form, array $placeholders): string
    {
        $template = $form->whatsappTemplate ?? null;

        if (!$template || empty($template->content)) {
            // Fallback: format default (persis seperti sebelum ada sistem template)
            $message = "Halo " . ($placeholders['name'] ?? '') . ",\n\n";
            $message .= "Terima kasih telah mengisi formulir \"" . ($placeholders['form_name'] ?? '') . "\".\n\n";

            if (!empty($placeholders['ringkasan_jawaban'])) {
                $message .= "*Ringkasan Jawaban:*\n";
                $message .= $placeholders['ringkasan_jawaban'] . "\n\n";
            }

            $message .= "Hasil Anda sudah kami terima. Terima kasih! 😊";
            $message .= $placeholders['universitas_major'] ?? '';

            $content = $message;
        } else {
            $content = $template->content;
        }

        foreach ($placeholders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        // === CALLBACK LINK ===
        // Kalau ada callback link untuk dikirim tapi template (atau format default di
        // atas) belum memuat placeholder {{callback_link}}, tambahkan section terpisah
        // di akhir pesan. Dicek dulu dengan str_contains supaya tidak dobel kalau
        // placeholder-nya memang sudah dipakai eksplisit di template.
        $callbackLink = $placeholders['callback_link'] ?? '';
        if (!empty($callbackLink) && !str_contains($content, $callbackLink)) {
            $content .= "\n\n🔗 *Link Anda:*\n" . $callbackLink;
        }

        // === PILIH KELAS LINK ===
        // Sama pola & alasannya dengan CALLBACK LINK di atas — kalau ada link
        // "Pilih Kelas" untuk dikirim (lihat ClassSchedule::existsActiveForBranch(),
        // dipakai di FrontendController::finalizeCompletedSubmission() & di
        // FormController::saveResult()) tapi template belum memuat placeholder
        // {{pilih_kelas_link}} secara eksplisit, tambahkan section terpisah di
        // akhir pesan.
        $pilihKelasLink = $placeholders['pilih_kelas_link'] ?? '';
        if (!empty($pilihKelasLink) && !str_contains($content, $pilihKelasLink)) {
            $content .= "\n\n📚 *Pilih Kelas Anda:*\n" . $pilihKelasLink;
        }

        return $content;
    }

    /**
     * Kirim pesan WhatsApp. Kredensial diambil dari gateway yang diaktifkan admin
     * pemilik form ($userId) lewat menu Settings > WhatsApp Gateway — sekarang
     * selalu mengarah ke Konexa/Teleios (satu-satunya provider yang didukung,
     * lihat WhatsappGateway::gatewayOptions()), lewat sendViaGateway() di bawah.
     * Kalau belum ada gateway yang diaktifkan untuk user itu, fallback ke
     * sendViaLegacyFallback() (kredensial Wablas lama di .env) supaya form yang
     * belum di-setting tetap jalan seperti sebelumnya.
     *
     * @return array|false
     */
    public function send(string $phone, string $message, ?string $userId = null)
    {
        try {
            // Clean phone number (remove all non-digits except +)
            $phone = preg_replace('/[^0-9+]/', '', $phone);

            // If phone starts with +62, replace with 62
            if (str_starts_with($phone, '+62')) {
                $phone = '62' . substr($phone, 3);
            } elseif (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $gateway = $userId
                ? WhatsappGateway::where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('status', 'active')
                    ->first()
                : null;

            $response = $gateway
                ? $this->sendViaGateway($gateway, $phone, $message)
                : $this->sendViaLegacyFallback($phone, $message);

            Log::info('WhatsApp Gateway Response - WhatsappMessenger', [
                'phone' => $phone,
                'gateway_id' => $gateway->id ?? null,
                'body' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Gateway Error - WhatsappMessenger', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Kirim lewat gateway yang di-setting admin di Settings > WhatsApp Gateway.
     * Kontrak API-nya format Konexa/Teleios (BUKAN lagi format Wablas):
     *   POST {api_host}/api/wa-api/v1/send-message
     *   Header: X-WA-Token, X-WA-Secret (dua header terpisah, bukan digabung
     *   jadi satu "Authorization: token.secret_key" seperti Wablas)
     *   Body: {"to": "<nomor atau JID>", "message": "<teks>"}
     * — lihat App\Http\Controllers\Api\WaApiSendMessageController &
     * App\Http\Middleware\VerifyWaApiKey di project Konexa/Teleios (backend WA
     * gateway-nya). $gateway->token / $gateway->secret_key diisi admin dari
     * pasangan token/secret yang digenerate di halaman Device Konexa/Teleios.
     */
    private function sendViaGateway(WhatsappGateway $gateway, string $phone, string $message)
    {
        $apiHost = rtrim($gateway->api_host, '/');

        return Http::withHeaders([
            'X-WA-Token' => $gateway->token,
            'X-WA-Secret' => $gateway->secret_key,
            'Content-Type' => 'application/json',
        ])->post($apiHost . '/api/wa-api/v1/send-message', [
            'to' => $phone,
            'message' => $message,
        ]);
    }

    /**
     * Fallback lama (format Wablas, kredensial dari .env) — HANYA dipakai kalau
     * form belum diasosiasikan ke user manapun ($userId null) atau user itu
     * belum men-setting gateway apa pun di Settings > WhatsApp Gateway. Sengaja
     * TIDAK diubah ke format Konexa/Teleios: ini jalur legacy independen dari
     * WhatsappGateway model, tetap menembak host Wablas asli seperti sebelumnya
     * supaya form lama yang masih mengandalkannya tidak putus.
     */
    private function sendViaLegacyFallback(string $phone, string $message)
    {
        $authorization = env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET');

        return Http::withHeaders([
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
        ])->post('https://smg.wablas.com/api/v2/send-message', [
            'data' => [
                [
                    'phone' => $phone,
                    'message' => $message,
                ]
            ]
        ]);
    }
}
