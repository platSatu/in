<?php

namespace App\Http\Controllers\Absensi;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\AttendanceUserQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AttendanceUserQrCodeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            AttendanceUserQrCode::class,
            (string) $userId,
            ['qr_token', 'status'],
            $search,
            10,
            ['user']
        );

        return view('absensi.attendance-user-qr-code.index', compact('data'));
    }

    public function create()
    {
        return view('absensi.attendance-user-qr-code.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
            'expires_at' => 'nullable|date',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $token = $this->generateNumericToken();
        $qrPath = $this->generateQrPngFromToken($token);

        $validated['user_id'] = (string) $userId;
        $validated['qr_token'] = $token;
        $validated['qr_code_path'] = $qrPath;
        $validated['last_rotated_at'] = now();
        $validated['expires_at'] = !empty($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])
            : now()->addDay();

        AdminCrud::create(AttendanceUserQrCode::class, $validated);

        return redirect()
            ->route('absensi.attendance-user-qr-code.index')
            ->with('success', 'Attendance user QR code berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(AttendanceUserQrCode::class, $id, (string) $userId, ['user']);

        return view('absensi.attendance-user-qr-code.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(AttendanceUserQrCode::class, $id, (string) $userId);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
            'expires_at' => 'nullable|date',
        ]);

        AdminCrud::update(AttendanceUserQrCode::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('absensi.attendance-user-qr-code.index')
            ->with('success', 'Attendance user QR code berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(AttendanceUserQrCode::class, $id, (string) $userId);

        if (!empty($data->qr_code_path) && Storage::disk('public')->exists($data->qr_code_path)) {
            Storage::disk('public')->delete($data->qr_code_path);
        }

        AdminCrud::delete(AttendanceUserQrCode::class, $id, (string) $userId);

        return redirect()
            ->route('absensi.attendance-user-qr-code.index')
            ->with('success', 'Attendance user QR code berhasil dihapus.');
    }

    public function generateQr(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(AttendanceUserQrCode::class, $id, (string) $userId);

        if (!empty($data->qr_code_path) && Storage::disk('public')->exists($data->qr_code_path)) {
            Storage::disk('public')->delete($data->qr_code_path);
        }

        $token = $this->generateNumericToken();
        $qrPath = $this->generateQrPngFromToken($token);

        $payload = [
            'qr_token' => $token,
            'qr_code_path' => $qrPath,
            'last_rotated_at' => now(),
            'expires_at' => now()->addDay(),
        ];

        AdminCrud::update(AttendanceUserQrCode::class, $id, $payload, (string) $userId);

        return redirect()
            ->route('absensi.attendance-user-qr-code.index')
            ->with('success', 'QR code berhasil digenerate ulang.');
    }

    private function generateNumericToken(): string
    {
        return str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    private function generateQrPngFromToken(string $token): string
    {
        $directory = 'attendance-qr';
        $filename = 'qr-' . $token . '.png';
        $relativePath = $directory . '/' . $filename;

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->create(500, 500)->fill('#ffffff');

        $fontPathCandidates = [
            public_path('frontend/src/assets/fonts/arial.ttf'),
            public_path('frontend/src/assets/fonts/Arial.ttf'),
            public_path('authLogin/assets/fonts/arial.ttf'),
            'C:\Windows\Fonts\arial.ttf',
        ];

        $fontPath = null;
        foreach ($fontPathCandidates as $candidate) {
            if (file_exists($candidate)) {
                $fontPath = $candidate;
                break;
            }
        }

        $image->text(
            $token,
            250,
            250,
            function ($font) use ($fontPath) {
                if ($fontPath !== null) {
                    $font->filename($fontPath);
                }
                $font->size(72);
                $font->color('#000000');
                $font->align('center');
                $font->valign('middle');
            }
        );

        Storage::disk('public')->put($relativePath, (string) $image->toPng());

        return $relativePath;
    }
}
