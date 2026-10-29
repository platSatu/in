<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Menu Settings > Backup Database: daftar backup harian, tombol "Backup Sekarang", dan unduh.
 * Akses lewat permission 'settings.backup' (lihat config/menu.php): lihat = daftar & unduh, edit = Backup Sekarang.
 */
class DatabaseBackupController extends Controller
{
    public function index(DatabaseBackup $backup): View
    {
        return view('settings.backup.index', [
            'backups' => $backup->list(),
            'keep' => DatabaseBackup::KEEP,
            'canBackup' => (bool) auth()->user()?->canAccessPermission('settings.backup', 'edit'),
        ]);
    }

    public function store(DatabaseBackup $backup): RedirectResponse
    {
        set_time_limit(0);
        ignore_user_abort(true);

        try {
            $name = $backup->create();
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Backup {$name} berhasil dibuat.");
    }

    public function download(DatabaseBackup $backup, string $file): BinaryFileResponse
    {
        abort_unless($path = $backup->find($file), 404);

        return response()->download($path, $file, ['Content-Type' => 'application/gzip']);
    }
}
