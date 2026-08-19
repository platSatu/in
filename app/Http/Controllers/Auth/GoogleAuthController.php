<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HistoryUserLogin;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Login / register pakai akun Google (Gmail).
 *
 * - Kalau email dari akun Google itu sudah terdaftar -> langsung login-kan.
 * - Kalau belum -> daftarkan otomatis (register) lalu login-kan.
 *
 * Karena Google sudah memverifikasi kepemilikan email itu sendiri, user yang
 * masuk lewat jalur ini langsung dianggap verified & active (tidak perlu
 * lagi verifikasi email manual seperti jalur registrasi biasa).
 */
class GoogleAuthController extends Controller
{
    private const STUDENT_ROLE_ID = '019eddb7-8f13-733a-805f-e071502b5dc9';

    /**
     * Redirect ke halaman consent Google.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback dari Google setelah user memberi izin.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::error('Google login gagal', ['error' => $e->getMessage()]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.']);
        }

        if (!$googleUser->getEmail()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Google Anda tidak memiliki email yang bisa diverifikasi.']);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'User',
                'email' => $googleUser->getEmail(),
                'handphone' => '',
                'password' => Hash::make(Str::random(40)),
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => self::STUDENT_ROLE_ID,
                'status' => RoleUser::STATUS_ACTIVE,
            ]);
        } elseif (!$user->hasVerifiedEmail() || !$user->isActive()) {
            // Email Google ini sudah dipakai daftar manual tapi belum
            // diverifikasi -> login via Google otomatis menganggap email
            // itu valid, jadi langsung aktifkan.
            $user->forceFill([
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);

        HistoryUserLogin::create([
            'user_id' => $user->id,
            'last_login' => now(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
