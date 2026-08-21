<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            // Dulu tidak divalidasi sama sekali padahal field-nya wajib diisi
            // di form (auth/register.blade.php) dan langsung dipakai buat
            // kirim WA selamat datang (sendWhatsapp() di bawah) — kalau
            // kosong, WA welcome message-nya gagal terkirim ke nomor kosong.
            'handphone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'handphone' => $request->handphone,
            'password' => Hash::make($request->password),
            // Akun baru selalu dimulai inactive. Baru berubah menjadi active
            // otomatis lewat VerifyEmailController setelah link verifikasi
            // di email diklik.
            'status' => User::STATUS_INACTIVE,
        ]);

        RoleUser::create([
            'user_id' => $user->id,
            'role_id' => '019eddb7-8f13-733a-805f-e071502b5dc9',
            'status' => RoleUser::STATUS_ACTIVE,
        ]);

        // Kirim WhatsApp
        $this->sendWhatsapp(
            $user->handphone,
            "Halo {$user->name},\n\nTerima kasih telah mendaftar di aplikasi kami.\n\nSelamat bergabung 😊"
        );

        // Event ini otomatis mengirimkan email verifikasi ke user (lewat
        // listener bawaan Laravel), karena User sekarang implements
        // MustVerifyEmail.
        event(new Registered($user));

        // Tetap login-kan user supaya link verifikasi (yang route-nya
        // butuh middleware 'auth') bisa langsung dibuka. Tapi selama status
        // masih inactive, middleware 'verified' pada route dashboard akan
        // otomatis mengarahkan user ke halaman "cek email Anda" sampai dia
        // klik link verifikasi.
        Auth::login($user);

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Registrasi berhasil! Kami sudah mengirimkan email verifikasi ke ' . $user->email . '. Silakan cek email Anda (termasuk folder spam) dan klik link verifikasi untuk mengaktifkan akun.');
    }


    private function sendWhatsapp($phone, $message)
    {
        try {

            $response = Http::withHeaders([
                'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://smg.wablas.com/api/v2/send-message', [
                        'data' => [
                            [
                                'phone' => $phone,
                                'message' => $message,
                            ]
                        ]
                    ]);

            // Simpan response ke log
            Log::info('Wablas Response', [
                'phone' => $phone,
                'body' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Wablas Error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
