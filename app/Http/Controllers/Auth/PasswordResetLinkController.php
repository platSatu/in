<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'cf-turnstile-response' => [new Turnstile()],
        ], [
            'email.exists' => 'Email tersebut belum terdaftar di sistem kami.',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // FIX 29 Okt 2026: SMTP gagal tidak lagi menampilkan error 500.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $e) {
            Log::error('[Forgot Password] Email reset gagal dikirim', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);
            $status = 'mail-failed';
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password sudah kami kirim ke email Anda. Silakan cek inbox (termasuk folder spam) dan klik link tersebut dalam 60 menit.');
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => match ($status) {
            Password::RESET_THROTTLED => 'Link reset baru saja dikirim. Silakan cek email Anda, atau tunggu sekitar 1 menit sebelum meminta lagi.',
            default => 'Link reset password belum bisa dikirim. Silakan coba lagi.',
        }]);
    }
}
