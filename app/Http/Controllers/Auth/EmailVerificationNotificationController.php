<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // FIX 29 Okt 2026: SMTP gagal tidak lagi menampilkan error 500.
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            Log::error('[Verifikasi] Kirim ulang email gagal', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('warning', 'Email verifikasi belum berhasil terkirim. Silakan coba lagi beberapa saat lagi.');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
