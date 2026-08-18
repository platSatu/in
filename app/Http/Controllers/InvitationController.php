<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    // =========================================================
    // FORM — public, tidak perlu login
    // =========================================================
    public function create()
    {
        return view('invitations.create');
    }

    // =========================================================
    // STORE — public, tidak perlu login
    // =========================================================
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'handphone' => 'required|string|max:20',
    //         'university' => 'nullable|string|max:255',
    //         'program' => 'nullable|string|max:255',
    //         'number_of_attendes' => 'nullable|integer|min:1',
    //     ]);

    //     // ── Generate QR code value ────────────────────────────
    //     $qrcodeValue = 'INA-' . strtoupper(Str::random(10));

    //     // ── Simpan QR code di public/qrcode/ ─────────────────
    //     $directory = public_path('qrcode');
    //     if (!file_exists($directory)) {
    //         mkdir($directory, 0755, true);
    //     }

    //     $filename = $qrcodeValue . '.png';
    //     $filepath = $directory . '/' . $filename;

    //     QrCode::format('png')->size(300)->generate($qrcodeValue, $filepath);

    //     // ── Simpan ke DB ──────────────────────────────────────
    //     $invitation = Invitation::create([
    //         ...$validated,
    //         'status' => 'tidak_hadir',
    //         'qrcode' => $qrcodeValue,
    //         'directory_qrcode' => 'qrcode/' . $filename,
    //     ]);

    //     // ── Kirim WhatsApp ────────────────────────────────────
    //     $this->sendWhatsappInvitation($invitation);

    //     return redirect()
    //         ->route('invitation.create')
    //         ->with('success', 'Terima kasih! Undangan berhasil dikirim ke WhatsApp Anda.');
    // }
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'handphone' => 'required|string|max:20',
    //         'university' => 'nullable|string|max:255',
    //         'program' => 'nullable|string|max:255',
    //         'number_of_attendes' => 'nullable|integer|min:1',
    //     ]);

    //     // ── Generate QR code value ────────────────────────────
    //     $qrcodeValue = 'INA-' . strtoupper(Str::random(10));

    //     // ── Simpan QR code di public/qrcode/ ─────────────────
    //     $directory = public_path('qrcode');
    //     if (!file_exists($directory)) {
    //         mkdir($directory, 0755, true);
    //     }

    //     $filename = $qrcodeValue . '.png';
    //     $filepath = $directory . '/' . $filename;

    //     QrCode::format('png')->size(400)->generate($qrcodeValue, $filepath);

    //     // ── Simpan ke DB ──────────────────────────────────────
    //     $invitation = Invitation::create([
    //         ...$validated,
    //         'status' => 'tidak_hadir',
    //         'qrcode' => $qrcodeValue,
    //         'directory_qrcode' => 'qrcode/' . $filename,
    //     ]);

    //     // ── Kirim WhatsApp ────────────────────────────────────
    //     $this->sendWhatsappInvitation($invitation);

    //     // ── Redirect ke halaman show ──────────────────────────
    //     // return redirect()->route('invitation.show', $invitation->id);
    //     return redirect()
    //         ->route('invitation.create')
    //         ->with('success', 'Invitation has been successfully created and sent via WhatsApp.');

    // }
    public function store(Request $request)
    {
        $validated = $request->validate([
            //'name' => 'required|string|max:255','regex:/^[A-Za-z\s\.\'-]+$/',
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z\s\.\'-]+$/',
            ],
            //'handphone' => 'required|string|max:20',
            'handphone' => 'required|regex:/^[0-9]{10,20}$/',
            'university' => 'required|string|max:255',
            'program' => 'required|string|max:255',
            //'number_of_attendes' => 'nullable|integer|min:1',
            'number_of_attendes' => 'required|integer|min:1|max:4',
            ],
            [
            'name.regex' => 'The name may only contain letters, spaces, apostrophes, hyphens, and periods.',
            'handphone.regex' => 'The WhatsApp number must contain only digits and be between 10 and 20 digits long.',
            'university.required' => 'Please enter your university.',
            'program.required' => 'Please select a program.',
            'number_of_attendes.required' => 'Please enter the number of attendees.',
            ]);

        DB::transaction(function () use ($validated) {
            // ── Generate QR code value ────────────────────────────
            $qrcodeValue = 'INA-' . strtoupper(Str::random(10));

            // ── Simpan QR code di public/qrcode/ ─────────────────
            $directory = public_path('qrcode');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = $qrcodeValue . '.png';
            $filepath = $directory . '/' . $filename;

            // ── Generate QR (harus sama hasil & parameter) ─────
            QrCode::format('png')->size(400)->generate($qrcodeValue, $filepath);

            // ── Simpan ke DB ──────────────────────────────────────
            $invitation = Invitation::create([
                ...$validated,
                'status' => 'tidak_hadir',
                'qrcode' => $qrcodeValue,
                'directory_qrcode' => 'qrcode/' . $filename,
            ]);

            Log::info([
                'id' => $invitation->id,
                'hex' => bin2hex($invitation->id),
            ]);

            // ── Kirim WhatsApp ────────────────────────────────────
            $this->sendWhatsappInvitation($invitation);
        });

        return redirect()
            ->route('invitation.create')
            ->with('success', 'Invitation has been successfully created and sent via WhatsApp.');
    }




    // public function show(string $id)
    // {
    //     $invitation = Invitation::findOrFail($id);
    //     return view('invitations.show', compact('invitation'));
    // }
    public function show($qrcode)
    {
        $invitation = Invitation::where('qrcode', $qrcode)->firstOrFail();

        return view('invitations.show', compact('invitation'));
    }


    // =========================================================
    // PRIVATE: Kirim WhatsApp (teks + gambar QR)
    // =========================================================
    private function sendWhatsappInvitation(Invitation $invitation): bool
    {
        try {
            $phone = $this->formatPhone($invitation->handphone);

            // ── Pesan teks ────────────────────────────────────

            $message = "Dear. *{$invitation->name}*\n\n";
            $message .= "We’ve been planning something special just for you, and you’re invited to our Departure Briefing Ceremony! We’d love to see you there!.\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";

            $message .= "```\n";

            $message .= "Name       : {$invitation->name}\n";

            if ($invitation->university) {
                $message .= "University : {$invitation->university}\n";
            }

            if ($invitation->program) {
                $message .= "Program    : {$invitation->program}\n";
            }

            if ($invitation->number_of_attendes) {
                $message .= "Attendees  : {$invitation->number_of_attendes} people\n";
            }

            $message .= "\n";
            $message .= "Date       : 1 August 2026\n";
            $message .= "Time       : 3 PM - Finished\n";
            $message .= "Location   : Flix Cinema, 2nd Floor Mall of Indonesia\n";
            $message .= "Maps       : https://maps.google.com\n";

            $message .= "```\n";

            $message .= "━━━━━━━━━━━━━━━━━━━━\n";

            //$cleanId = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $invitation->id);

            //$showUrl = route('invitation.show', $cleanId);

            //$showUrl = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $showUrl);

            //$message .= "\n*Save for Check-in* : " . $showUrl . "\n\n";
            //$message .= "\n";
            //$message .= "🔗 *Click here for check-in:*\n";

            //$message .= $showUrl . "\n\n";
            $cleanId = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($invitation->qrcode));

            // $showUrl = route('invitation.show', ['id' => $cleanId]);
            // $showUrl = route('invitation.show', [
            //     'qrcode' => $invitation->qrcode
            // ]);

            // Bersihkan jika masih ada karakter tersembunyi di URL
            //$showUrl = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $showUrl);
            //$showUrl = trim($showUrl);
            $cleanQrcode = preg_replace('/[^\x20-\x7E]/u', '', trim($invitation->qrcode));
            $showUrl     = rtrim(config('app.url'), '/') . '/invitation/' . $cleanQrcode;

            // Pastikan tidak ada karakter non-ASCII di URL
            $showUrl = preg_replace('/[^\x20-\x7E]/', '', $showUrl);
            $showUrl = trim($showUrl);

            $message .= "\n";
            $message .= "🔗 *Click here for check-in:*\n";
            $message .= $showUrl . "\n\n";

            $message .= "*InaStudy*";

            Log::info('Message Hex Check', [
                'has_zero_width' => preg_match('/[\x{200B}-\x{200D}\x{FEFF}]/u', $message),
                'id' => $invitation->id,
                'id_hex' => bin2hex($invitation->id),
                'url' => $showUrl,
                'url_hex' => bin2hex($showUrl),
                'message' => $message,
            ]);

            // ── Kirim pesan teks ──────────────────────────────
            $textResponse = Http::withHeaders([
                'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://smg.wablas.com/api/v2/send-message', [
                        'data' => [
                            [
                                'phone' => $phone,
                                'message' => $message,
                            ]
                        ],
                    ]);

            Log::info('Wablas Text - Invitation', [
                'phone' => $phone,
                'body' => $textResponse->json(),
            ]);

            // ── Kirim gambar QR code ──────────────────────────
            $qrPublicPath = public_path($invitation->directory_qrcode);
            if (file_exists($qrPublicPath)) {
                $qrImageUrl = url($invitation->directory_qrcode);

                $imageResponse = Http::withHeaders([
                    'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                    'Content-Type' => 'application/json',
                ])->post('https://smg.wablas.com/api/v2/send-image', [
                            'data' => [
                                [
                                    'phone' => $phone,
                                    'image' => $qrImageUrl,
                                    'caption' => "🎫 QR Code Undangan\n*{$invitation->name}*\nKode: {$invitation->qrcode}",
                                ]
                            ],
                        ]);

                Log::info('Wablas Image - Invitation', [
                    'phone' => $phone,
                    'image_url' => $qrImageUrl,
                    'body' => $imageResponse->json(),
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Wablas Error - Invitation', [
                'invitation_id' => $invitation->id,
                'phone' => $invitation->handphone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // =========================================================
    // PRIVATE: Format nomor HP ke 62xxx
    // =========================================================
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+62')) {
            return '62' . substr($phone, 3);
        }
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        return $phone;
    }
}
