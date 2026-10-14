<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentIdentityResolver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * Ini juga yang mengubah status user dari 'inactive' menjadi 'active' —
     * itu-lah momen "link diklik" yang dimaksud requirement registrasi.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // FIX keamanan (14 Oktober 2026): link verifikasi yang dibuka ULANG
        // (email sudah terverifikasi) tidak lagi mengaktifkan akun -- dulu
        // akun yang sengaja dinonaktifkan admin bisa aktif lagi lewat sini.
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        // Email sudah terbukti miliknya -> baru sekarang data Student (CRM)
        // ditautkan/dibuat (dulu saat register, lihat RegisteredUserController).
        if ($user->hasRole('student')) {
            (new StudentIdentityResolver())->resolveForUser($user);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
