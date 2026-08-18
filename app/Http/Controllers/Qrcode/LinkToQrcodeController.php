<?php
/*
|--------------------------------------------------------------------------
| CATATAN
|--------------------------------------------------------------------------
| - Butuh package: simplesoftwareio/simple-qrcode
|       composer require simplesoftwareio/simple-qrcode
|   Package ini butuh ext-gd atau ext-imagick aktif di server.
|
| - QR code DIGENERATE SEKALI saja (saat store/update), disimpan sebagai file
|   PNG di public/qrcodes, lalu file yang sama itu terus dipakai di semua
|   tampilan (index/edit/show/download) — bukan digenerate ulang tiap kali
|   halaman dibuka. Ini supaya hasil QR-nya konsisten/sama setiap saat dilihat.
|
| - Kalau link diubah lewat update(), QR lama dihapus dan digenerate ulang
|   dari link yang baru, supaya QR yang tersimpan selalu sesuai isi link
|   yang aktif sekarang.
|
| - Kolom `qrcode` menyimpan nama file (mis. "uuid.png"), kolom
|   `directory_qrcode` menyimpan nama folder (mis. "qrcodes"). Path lengkap
|   di server = public_path("{$directory_qrcode}/{$qrcode}"), dan yang
|   diakses browser = asset("{$directory_qrcode}/{$qrcode}").
*/

namespace App\Http\Controllers\Qrcode;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\LinkQrcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LinkToQrcodeController extends Controller
{
    /**
     * Folder tujuan penyimpanan file QR code, relatif terhadap public_path().
     */
    private string $qrDirectory = 'qrcodes';

    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            LinkQrcode::class,
            (string) $userId,
            ['name', 'link', 'description'],
            $search,
            10
        );

        return view('qrcodes.index', compact('data'));
    }

    public function create()
    {
        return view('qrcodes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'required|string|max:2048',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated['user_id'] = (string) $userId;

        $qr = $this->generateQrCodeFile($validated['link']);
        $validated['qrcode'] = $qr['filename'];
        $validated['directory_qrcode'] = $qr['directory'];

        AdminCrud::create(LinkQrcode::class, $validated);

        return redirect()
            ->route('qrcodes.index')
            ->with('success', 'QR Code berhasil dibuat.');
    }

    /**
     * Halaman khusus menampilkan 1 QR code besar + tombol download & share.
     */
    public function show(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(LinkQrcode::class, $id, (string) $userId);

        return view('qrcodes.show', compact('data'));
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(LinkQrcode::class, $id, (string) $userId);

        return view('qrcodes.edit', compact('data'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(LinkQrcode::class, $id, (string) $userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'required|string|max:2048',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Link bisa berubah -> QR lama sudah tidak relevan, jadi selalu digenerate ulang
        // supaya QR yang tersimpan selalu sesuai isi link yang aktif sekarang.
        $qr = $this->generateQrCodeFile($validated['link']);
        $this->deleteQrCodeFile($existing->directory_qrcode, $existing->qrcode);

        $validated['qrcode'] = $qr['filename'];
        $validated['directory_qrcode'] = $qr['directory'];

        AdminCrud::update(LinkQrcode::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('qrcodes.index')
            ->with('success', 'QR Code berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $existing = AdminCrud::findOrFail(LinkQrcode::class, $id, (string) $userId);

        $this->deleteQrCodeFile($existing->directory_qrcode, $existing->qrcode);

        AdminCrud::delete(LinkQrcode::class, $id, (string) $userId);

        return redirect()
            ->route('qrcodes.index')
            ->with('success', 'QR Code berhasil dihapus.');
    }

    /**
     * Generate QR code PNG dari $link, simpan ke public/{qrDirectory}, kembalikan
     * nama folder & filename-nya.
     */
    private function generateQrCodeFile(string $link): array
    {
        $destination = public_path($this->qrDirectory);

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = Str::uuid() . '.png';

        QrCode::format('png')
            ->size(400)
            ->margin(1)
            ->generate($link, $destination . '/' . $filename);

        return [
            'directory' => $this->qrDirectory,
            'filename' => $filename,
        ];
    }

    /**
     * Hapus file QR lama dari public/{directory}, kalau ada.
     */
    private function deleteQrCodeFile(?string $directory, ?string $filename): void
    {
        if (!$directory || !$filename) {
            return;
        }

        $fullPath = public_path($directory . '/' . $filename);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}