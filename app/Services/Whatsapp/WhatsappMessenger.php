<?php

namespace App\Services\Whatsapp;

use App\Models\Form;
use App\Models\WhatsappGateway;
use App\Models\WhatsappTemplate;
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
     * === FITUR TAMBAHAN: TEMPLATE WA PER OPSI JAWABAN ===
     *
     * Susun isi pesan WhatsApp dari template MILIK SATU OPSI jawaban
     * (form_question_options.whatsapp_template_id), BUKAN dari
     * $form->whatsappTemplate. Method BARU & TERPISAH dari
     * buildMessageFromTemplate() di atas -- sengaja tidak memodifikasi method
     * itu sama sekali, supaya perilaku pesan per-Form yang sudah berjalan
     * (dipakai FrontendController::finalizeCompletedSubmission() &
     * FormController::saveResult()) tidak berubah sedikit pun.
     *
     * Dipakai FrontendController::sendPerOptionWhatsappMessages() untuk
     * mengirim pesan TAMBAHAN per opsi yang dipilih peserta & punya template
     * terpasang -- di luar (bukan pengganti) pesan per-Form yang sudah ada.
     *
     * Beda dengan buildMessageFromTemplate(): TIDAK ada fallback ke format
     * pesan default kalau template/isinya kosong -- kembalikan null saja,
     * biar caller cukup skip pengiriman utk opsi itu (daripada memaksa kirim
     * pesan default yang bisa dobel dengan pesan per-Form yang sudah terkirim
     * duluan).
     *
     * @param array $placeholders key => value, key TANPA kurung kurawal, misal 'name' utk {{name}}
     */
    public function buildMessageFromWhatsappTemplate(WhatsappTemplate $template, array $placeholders): ?string
    {
        if (empty($template->content)) {
            return null;
        }

        $content = $template->content;

        foreach ($placeholders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        // Sama pola dengan buildMessageFromTemplate(): tambahkan link di akhir
        // pesan kalau ada tapi belum dipakai eksplisit lewat placeholder di
        // template-nya sendiri.
        $callbackLink = $placeholders['callback_link'] ?? '';
        if (!empty($callbackLink) && !str_contains($content, $callbackLink)) {
            $content .= "\n\n🔗 *Link Anda:*\n" . $callbackLink;
        }

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

            // BUGFIX (per keputusan owner): gateway ini SEKARANG berlaku
            // system-wide untuk semua form, tidak peduli form itu dibuat
            // admin siapa -- sebelumnya di-scope ketat ke user_id PEMBUAT
            // FORM ($userId param di bawah), jadi kalau WhatsApp Gateway
            // di-setting oleh admin A tapi form dibuat admin B, WA tidak
            // pernah kedetect & selalu jatuh ke sendViaLegacyFallback()
            // (kredensial Wablas lama, sering sudah expired). $userId
            // sengaja dibiarkan di signature (dipakai banyak caller) tapi
            // TIDAK dipakai lagi buat filter -- cukup 1 gateway aktif utk
            // seluruh sistem (lihat WhatsappGatewayController::deactivateOthers()
            // yang juga sudah tidak di-scope per user lagi).
            $gateway = WhatsappGateway::where('is_active', true)
                ->where('status', 'active')
                ->latest('updated_at')
                ->first();

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
