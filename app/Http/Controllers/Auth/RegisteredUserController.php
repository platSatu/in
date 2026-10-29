<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\Whatsapp\WhatsappMessenger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            // Wajib diisi di form dan dipakai untuk WA selamat datang.
            'handphone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cf-turnstile-response' => [new Turnstile()],
        ]);

        // User + role dibuat bersamaan: kalau salah satu gagal, tidak ada akun setengah jadi.
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'handphone' => $request->handphone,
                'password' => Hash::make($request->password),
                // Akun baru selalu inactive, baru active setelah link verifikasi
                // di email diklik (VerifyEmailController).
                'status' => User::STATUS_INACTIVE,
            ]);

            RoleUser::create([
                'user_id' => $user->id,
                'role_id' => $this->resolveStudentRoleId(),
                'status' => RoleUser::STATUS_ACTIVE,
            ]);

            return $user;
        });

        // FIX keamanan (14 Oktober 2026): Student (CRM) TIDAK ditautkan di sini,
        // baru setelah email terverifikasi (VerifyEmailController ->
        // StudentIdentityResolver::resolveForUser(), cocok lewat email saja).

        // Login-kan dulu supaya link verifikasi (route butuh 'auth') bisa langsung dibuka.
        // Selama belum verifikasi, middleware 'verified' mengarahkan ke halaman "cek email".
        Auth::login($user);

        // Email verifikasi (listener bawaan Laravel, karena User implements MustVerifyEmail).
        // FIX 29 Okt 2026: kalau SMTP gagal, dulu user melihat error 500 padahal akunnya
        // sudah terbuat (daftar ulang ditolak "email sudah terdaftar"). Sekarang tetap
        // lanjut ke halaman verifikasi dengan pesan untuk menekan "Kirim Ulang".
        try {
            event(new Registered($user));
            $flash = ['status' => 'Registrasi berhasil! Kami sudah mengirimkan email verifikasi ke ' . $user->email . '. Silakan cek email Anda (termasuk folder spam) dan klik link verifikasi untuk mengaktifkan akun.'];
        } catch (Throwable $e) {
            Log::error('[Register] Email verifikasi gagal dikirim', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            $flash = ['warning' => 'Akun Anda sudah terdaftar, tetapi email verifikasi belum berhasil terkirim. Silakan tekan tombol "Kirim Ulang Email Verifikasi" di bawah beberapa saat lagi.'];
        }

        // WA selamat datang lewat gateway aktif (Konexa), sama seperti balasan quiz
        // dan pengingat dokumen. FIX 29 Okt 2026: dulu menembak Wablas langsung dengan
        // token lama di .env ("token invalid or device expired"), jadi tidak pernah
        // terkirim. Dikirim setelah halaman tampil supaya register tidak menunggu WA.
        $this->sendWelcomeWhatsappAfterResponse($user);

        return redirect()->route('verification.notice')->with($flash);
    }

    private function sendWelcomeWhatsappAfterResponse(User $user): void
    {
        $phone = trim((string) $user->handphone);
        if ($phone === '') {
            return;
        }

        $message = "Halo {$user->name},\n\nTerima kasih telah mendaftar di aplikasi kami.\n\nSelamat bergabung 😊";

        app()->terminating(function () use ($phone, $message) {
            try {
                (new WhatsappMessenger())->send($phone, $message);
            } catch (Throwable $e) {
                Log::error('[Register] WA selamat datang gagal', ['phone' => $phone, 'error' => $e->getMessage()]);
            }
        });
    }

    // Fix (14 & 16 September 2026, BUGFIX): role student dicari lewat slug dan dibuat
    // otomatis kalau belum ada, supaya tidak bergantung ke ID hardcode atau slug yang
    // bisa berubah saat nama role diedit di halaman Roles. Pola yang sama dipakai di
    // GoogleAuthController & StudentController::addUser().
    private function resolveStudentRoleId(): string
    {
        return Role::firstOrCreate(
            ['slug' => 'student'],
            [
                'name' => 'Student',
                'status' => Role::STATUS_ACTIVE,
            ]
        )->id;
    }
}
