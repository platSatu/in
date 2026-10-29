<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HistoryUserLogin;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use App\Services\StudentIdentityResolver;
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
    // Fix (14 September 2026, permintaan user -- BUGFIX): konstanta ID
    // hardcode di sini dihapus -- ID yang sama ternyata dipakai juga di
    // RegisteredUserController & StudentController::addUser(), jadi begitu
    // role dengan ID itu di-EDIT namanya lewat halaman Roles (mis. tanpa
    // sadar jadi "Sales"), user baru yang login lewat Google ikut ke-assign
    // role yang salah walau ID-nya tidak berubah. Sekarang dicari dinamis
    // lewat slug "student" -- lihat resolveStudentRoleId() di bawah.

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

        // FIX 29 Okt 2026: hanya terima email yang sudah diverifikasi Google.
        // Akun Gmail selalu terverifikasi; ini menjaga akun Google dengan email
        // non-Gmail yang belum diverifikasi supaya tidak bisa masuk ke akun orang lain.
        $emailVerified = $googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? true;
        if (filter_var($emailVerified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
            Log::warning('Google login ditolak: email belum diverifikasi Google', ['email' => $googleUser->getEmail()]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Email akun Google Anda belum diverifikasi oleh Google. Silakan verifikasi dulu atau daftar dengan email dan password.']);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        // FIX keamanan (14 Oktober 2026): akun yang dinonaktifkan admin tidak
        // boleh masuk -- dulu login Google malah mengaktifkannya kembali.
        if ($user && $user->isSuspended()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => User::INACTIVE_MESSAGE]);
        }

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'User',
                'email' => $googleUser->getEmail(),
                'handphone' => '',
                'password' => Hash::make(Str::random(40)),
                'status' => User::STATUS_ACTIVE,
            ]);

            // BUGFIX: 'email_verified_at' SENGAJA tidak ada di User::$fillable
            // (supaya tidak bisa disetel lewat form/endpoint lain yang mungkin
            // mass-assignment dari input user) -- jadi kalau dikirim lewat
            // User::create([...]) di atas, Eloquent DIAM-DIAM mengabaikannya
            // (bukan error), hasilnya user Google tetap punya email_verified_at
            // NULL padahal status-nya sudah 'active'. Makanya di-set terpisah
            // di sini lewat forceFill(), sama persis polanya dengan cabang
            // elseif di bawah untuk user existing.
            $user->forceFill(['email_verified_at' => now()])->save();

            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $this->resolveStudentRoleId(),
                'status' => RoleUser::STATUS_ACTIVE,
            ]);

            // Email sudah diverifikasi Google -> tautkan/buat Student (CRM),
            // dicocokkan lewat email saja (lihat StudentIdentityResolver::resolveForUser()).
            (new StudentIdentityResolver())->resolveForUser($user);
        } elseif (!$user->hasVerifiedEmail()) {
            // Email Google ini sudah dipakai daftar manual tapi belum
            // diverifikasi -> login via Google membuktikan email itu miliknya,
            // jadi langsung aktifkan.
            //
            // FIX keamanan (14 Oktober 2026): password lama diganti acak &
            // semua sesi lama dikeluarkan. Akun yang belum terverifikasi bisa
            // saja dibuat ORANG LAIN pakai email ini (password-nya dia yang
            // tahu) -- tanpa ini, orang itu ikut bisa masuk begitu pemilik
            // asli email login lewat Google. Pemilik asli tetap bisa pakai
            // password lewat "Forgot Password".
            $user->forceFill([
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ])->save();

            $user->logoutEverywhere();

            if ($user->hasRole('student')) {
                (new StudentIdentityResolver())->resolveForUser($user);
            }
        }

        Auth::login($user, true);

        HistoryUserLogin::create([
            'user_id' => $user->id,
            'last_login' => now(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function resolveStudentRoleId(): string
    {
        // FIX (permintaan user, 16 September 2026 -- BUGFIX): dulu abort(500)
        // kalau role "student" tidak ketemu lewat slug -- ternyata slug ini
        // gampang hilang diam-diam kalau admin edit nama role-nya di halaman
        // Roles tanpa isi field Slug (slug ikut di-generate ulang dari nama
        // baru, lihat RoleController::update()), jadi jalur signup Google ini
        // ikut error 500. Sama seperti StudentController::resolveStudentRoleId()
        // & RegisteredUserController::resolveStudentRoleId() -- role-nya
        // sekarang di-auto-provision (dibuat sekali kalau belum/tidak ketemu)
        // supaya tidak lagi bergantung ke 1 baris role yang bisa berubah
        // sewaktu-waktu.
        return Role::firstOrCreate(
            ['slug' => 'student'],
            [
                'name' => 'Student',
                'status' => Role::STATUS_ACTIVE,
            ]
        )->id;
    }
}
