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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'handphone' => $request->handphone,
            'password' => Hash::make($request->password),
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

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
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
