<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BackendInvitationController extends Controller
{
    // =========================================================
    // INDEX
    // =========================================================
    public function index(Request $request)
    {
        $query = Invitation::latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name',        'like', "%{$s}%")
                  ->orWhere('handphone', 'like', "%{$s}%")
                  ->orWhere('university','like', "%{$s}%")
                  ->orWhere('program',   'like', "%{$s}%")
                  ->orWhere('qrcode',    'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invitations = $query->paginate(10)->withQueryString();

        // $stats = [
        //     'total'       => Invitation::count(),
        //     'hadir'       => Invitation::where('status', 'hadir')->count(),
        //     'tidak_hadir' => Invitation::where('status', 'tidak_hadir')->count(),
        //     'pending'     => Invitation::where('status', 'pending')->count(),
        // ];
        $stats = [
            'total'       => Invitation::sum('number_of_attendes'),
            'hadir'       => Invitation::where('status', 'hadir')->sum('number_of_attendes'),
            'tidak_hadir' => Invitation::where('status', 'tidak_hadir')->sum('number_of_attendes'),
            'pending'     => Invitation::where('status', 'pending')->sum('number_of_attendes'),
        ];

        return view('dashboard.invitation.index', compact('invitations', 'stats'));
    }

    // =========================================================
    // CREATE
    // =========================================================
    public function create()
    {
        return view('dashboard.invitation.create');
    }

    // =========================================================
    // STORE
    // =========================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'handphone'          => 'required|string|max:20',
            'university'         => 'nullable|string|max:255',
            'program'            => 'nullable|string|max:255',
            'number_of_attendes' => 'nullable|integer|min:1',
            'status'             => 'required|in:hadir,tidak_hadir',
        ]);

        $invitation = $this->generateAndSave($validated);

        // Kirim WA
        $this->sendWhatsappInvitation($invitation);

        return redirect()
            ->route('dashboard.invitation.index')
            ->with('success', "Undangan untuk {$invitation->name} berhasil dibuat dan dikirim via WhatsApp.");
    }

    // =========================================================
    // SHOW
    // =========================================================
    public function show(Invitation $invitation)
    {
        return view('dashboard.invitation.show', compact('invitation'));
    }

    // =========================================================
    // EDIT
    // =========================================================
    public function edit(Invitation $invitation)
    {
        return view('dashboard.invitation.edit', compact('invitation'));
    }

    // =========================================================
    // UPDATE
    // =========================================================
    public function update(Request $request, Invitation $invitation)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'handphone'          => 'required|string|max:20',
            'university'         => 'nullable|string|max:255',
            'program'            => 'nullable|string|max:255',
            'number_of_attendes' => 'nullable|integer|min:1',
            'status'             => 'required|in:hadir,tidak_hadir',
        ]);

        $invitation->update($validated);

        return redirect()
            ->route('dashboard.invitation.index')
            ->with('success', "Undangan {$invitation->name} berhasil diperbarui.");
    }

    // =========================================================
    // DESTROY
    // =========================================================
    public function destroy(Invitation $invitation)
    {
        // Hapus file QR dari public/qrcode/
        if ($invitation->directory_qrcode) {
            $path = public_path($invitation->directory_qrcode);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $name = $invitation->name;
        $invitation->delete();

        return redirect()
            ->route('dashboard.invitation.index')
            ->with('success', "Undangan {$name} berhasil dihapus.");
    }

    // =========================================================
    // RESEND WHATSAPP
    // =========================================================
    public function resend(Invitation $invitation)
    {
        $sent = $this->sendWhatsappInvitation($invitation);

        return redirect()
            ->back()
            ->with(
                $sent ? 'success' : 'error',
                $sent
                    ? "WhatsApp berhasil dikirim ulang ke {$invitation->name}."
                    : 'Gagal kirim WhatsApp. Cek log untuk detail.'
            );
    }

    // =========================================================
    // PRIVATE: Generate QR & simpan ke DB
    // =========================================================
    private function generateAndSave(array $data): Invitation
    {
        $qrcodeValue = 'INA-' . strtoupper(Str::random(10));

        $directory = public_path('qrcode');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $qrcodeValue . '.png';
        QrCode::format('png')->size(400)->generate($qrcodeValue, $directory . '/' . $filename);

        return Invitation::create([
            ...$data,
            'qrcode'           => $qrcodeValue,
            'directory_qrcode' => 'qrcode/' . $filename,
        ]);
    }

    // =========================================================
    // PRIVATE: Kirim WhatsApp
    // =========================================================
    private function sendWhatsappInvitation(Invitation $invitation): bool
    {
        try {
            $phone   = $this->formatPhone($invitation->handphone);
           $message = "Dear. *{$invitation->name}*\n\n";
            $message .= "We’ve been planning somethi​ng special just for you, and you’re invited to our Departure Briefing Ceremony! We’d love to see you there!.\n\n";
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
            $message .= "Location   : MALL OF INDONESIA\n";
            $message .= "Maps       : https://maps.google.com\n";

            $message .= "```\n";

            $message .= "━━━━━━━━━━━━━━━━━━━━\n";

            $cleanId = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $invitation->id);

            $showUrl = route('invitation.show', $cleanId);

            //$showUrl = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $showUrl);

            //$message .= "\n*Save for Check-in* : " . $showUrl . "\n\n";
            $message .= "\n";
            $message .= "🔗 *Click here for check-in:*\n";

            $message .= $showUrl . "\n\n";

            $message .= "*InaStudy*";

            // Kirim teks
            Http::withHeaders([
                'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                'Content-Type'  => 'application/json',
            ])->post('https://smg.wablas.com/api/v2/send-message', [
                'data' => [['phone' => $phone, 'message' => $message]],
            ]);

            // Kirim gambar QR
            $qrPath = public_path($invitation->directory_qrcode);
            if (file_exists($qrPath)) {
                Http::withHeaders([
                    'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                    'Content-Type'  => 'application/json',
                ])->post('https://smg.wablas.com/api/v2/send-image', [
                    'data' => [[
                        'phone'   => $phone,
                        'image'   => url($invitation->directory_qrcode),
                        'caption' => "🎫 QR Code Undangan\n*{$invitation->name}*\nKode: {$invitation->qrcode}\n\n🔗 {$showUrl}",
                    ]],
                ]);
            }

            Log::info('WA Invitation Sent', ['phone' => $phone, 'id' => $invitation->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('WA Invitation Error', ['id' => $invitation->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    // =========================================================
    // PRIVATE: Format nomor HP
    // =========================================================
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+62')) return '62' . substr($phone, 3);
        if (str_starts_with($phone, '0'))   return '62' . substr($phone, 1);

        return $phone;
    }

   

// ============================================================
// Tambahkan 2 method ini ke BackendInvitationController
// ============================================================

// =========================================================
// SCAN PAGE
// =========================================================
public function RegisterUlangScan()
{
    $stats = [
        'total'  => Invitation::sum('number_of_attendes'),
        'hadir'  => Invitation::where('status', 'hadir')->sum('number_of_attendes'),
        'belum'  => Invitation::where('status', 'tidak_hadir')->sum('number_of_attendes'),
    ];

    return view('dashboard.invitation.register-ulang', compact('stats'));
}

// =========================================================
// PROCESS SCAN — dipanggil via AJAX dari blade
// =========================================================
public function processScan(Request $request)
{
    $request->validate([
        'qrcode' => 'required|string',
    ]);

    // Bersihkan karakter tersembunyi dari scanner
    $qrcode = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($request->qrcode));

    // 1. Cari invitation
    $invitation = Invitation::where('qrcode', $qrcode)->first();

    // 2. QR tidak ditemukan di sistem
    if (!$invitation) {
        return response()->json([
            'status'  => 'not_found',
            'message' => 'QR Code tidak ditemukan di sistem.',
        ], 404);
    }

    // 3. Sudah pernah hadir — tolak scan kedua
    if ($invitation->status === 'hadir') {
        return response()->json([
            'status'  => 'already',
            'message' => 'Sudah melakukan registrasi ulang sebelumnya.',
            'data'    => [
                'name'               => $invitation->name,
                'university'         => $invitation->university,
                'program'            => $invitation->program,
                'number_of_attendes' => $invitation->number_of_attendes,
                'updated_at'         => $invitation->updated_at->format('d M Y, H:i'),
            ],
        ], 409);
    }

    // 4. Update status tidak_hadir → hadir
    $invitation->update(['status' => 'hadir', 'checked_in_at' => now()]);

    return response()->json([
        'status'  => 'success',
        'message' => "Selamat datang, {$invitation->name}!",
        'data'    => [
            'name'               => $invitation->name,
            'university'         => $invitation->university,
            'program'            => $invitation->program,
            'number_of_attendes' => $invitation->number_of_attendes,
        ],
    ]);
 }
}
