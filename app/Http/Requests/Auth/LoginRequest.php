<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS_PER_EMAIL = 5;

    private const MAX_ATTEMPTS_PER_IP = 20;

    private const LOCKOUT_SECONDS = 900; // 15 menit

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => [new Turnstile()],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_SECONDS);
            RateLimiter::hit($this->ipThrottleKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Akun yang dinonaktifkan admin tidak boleh masuk (dulu tetap bisa).
        if (Auth::user()->isSuspended()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => User::INACTIVE_MESSAGE,
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * FIX 29 Okt 2026: dulu hanya 5x gagal per email+IP lalu dikunci 60 detik.
     * Sekarang:
     *   - 5x gagal untuk email yang sama dari IP yang sama -> dikunci 15 menit;
     *   - 20x gagal dari satu IP (email apa pun) -> IP itu dikunci 15 menit,
     *     supaya tidak bisa mencoba banyak email berbeda dari satu tempat.
     * Login yang berhasil hanya menghapus hitungan email itu, bukan hitungan IP.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        foreach ([$this->throttleKey() => self::MAX_ATTEMPTS_PER_EMAIL, $this->ipThrottleKey() => self::MAX_ATTEMPTS_PER_IP] as $key => $max) {
            if (! RateLimiter::tooManyAttempts($key, $max)) {
                continue;
            }

            event(new Lockout($this));

            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$minutes} menit, atau gunakan \"Forgot Password\" jika lupa password.",
            ]);
        }
    }

    /** Hitungan gagal per IP (semua email). */
    public function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
