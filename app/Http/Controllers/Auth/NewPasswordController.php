<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentIdentityResolver;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // FIX 29 Okt 2026: password baru -> semua sesi login lama di perangkat
                // lain ikut dikeluarkan (mis. kalau password lama sempat bocor).
                $user->logoutEverywhere();

                // FIX 29 Okt 2026: link reset dibuka dari inbox email ini, jadi
                // kepemilikan email sudah terbukti. Akun yang belum sempat verifikasi
                // langsung diverifikasi & diaktifkan (sama seperti VerifyEmailController),
                // supaya tidak tertahan di halaman "cek email". Akun yang sudah
                // terverifikasi tidak diubah statusnya -- akun yang dinonaktifkan
                // admin tetap nonaktif.
                if (! $user->hasVerifiedEmail()) {
                    if ($user->markEmailAsVerified()) {
                        event(new Verified($user));
                    }

                    $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

                    if ($user->hasRole('student')) {
                        (new StudentIdentityResolver())->resolveForUser($user);
                    }
                }

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan login dengan password baru Anda.');
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => match ($status) {
            Password::INVALID_TOKEN => 'Link reset password tidak valid atau sudah kedaluwarsa. Silakan minta link baru dari halaman Forgot Password.',
            Password::INVALID_USER => 'Email tidak sesuai dengan link reset password ini.',
            default => 'Password belum bisa diganti. Silakan coba lagi.',
        }]);
    }
}
