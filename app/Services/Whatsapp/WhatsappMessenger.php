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

        return $content;
    }

    /**
     * Kirim pesan WhatsApp. Kredensial diambil dari gateway yang diaktifkan admin
     * pemilik form ($userId) lewat menu Settings > WhatsApp Gateway. Kalau belum
     * ada gateway yang diaktifkan untuk user itu, fallback ke kredensial lama di
     * .env (WABLAS_TOKEN/WABLAS_SECRET) supaya form yang belum di-setting tetap
     * jalan seperti sebelumnya.
     *
     * Prosedur pengiriman sengaja disamakan untuk semua provider (Wablas-compatible):
     * POST {api_host}/api/v2/send-message, header Authorization: token.secret_key,
     * body {"data":[{"phone":...,"message":...}]}.
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

            if ($gateway) {
                $apiHost = rtrim($gateway->api_host, '/');
                $authorization = $gateway->token . '.' . $gateway->secret_key;
            } else {
                $apiHost = 'https://smg.wablas.com';
                $authorization = env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET');
            }

            $response = Http::withHeaders([
                'Authorization' => $authorization,
                'Content-Type' => 'application/json',
            ])->post($apiHost . '/api/v2/send-message', [
                        'data' => [
                            [
                                'phone' => $phone,
                                'message' => $message,
                            ]
                        ]
                    ]);

            Log::info('WhatsApp Gateway Response - WhatsappMessenger', [
                'phone' => $phone,
                'gateway_id' => $gateway->id ?? null,
                'api_host' => $apiHost,
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
}
