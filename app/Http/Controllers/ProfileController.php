<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Halaman "Profile" yang dibuka dari dropdown avatar (header/sidebar).
     * Cuma menampilkan data milik user yang login ($request->user()) — Name &
     * Email ditampilkan read-only di view, yang bisa diubah dari sini cuma
     * Foto & Password.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update foto dan/atau password milik user yang SEDANG LOGIN.
     *
     * Sengaja tidak menerima id user dari route/request sama sekali — semua
     * operasi di sini terikat ke $request->user() (dari session auth yang
     * sedang aktif), jadi seorang user tidak mungkin mengubah data user lain
     * lewat form ini walaupun dia mencoba memanipulasi payload request.
     * Name & Email juga sengaja TIDAK diambil dari request sama sekali (input-
     * nya disabled di view, tapi ini dijaga juga di sisi server: field itu
     * tidak pernah dibaca/divalidasi di sini) — jadi tetap aman meski ada yang
     * mencoba mem-bypass atribut disabled di browser.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $changes = [];

        if ($request->hasFile('image')) {
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            $changes['image'] = $this->storeImage($request->file('image'));
        }

        if (!empty($validated['new_password'])) {
            $changes['password'] = Hash::make($validated['new_password']);
        }

        if (empty($changes)) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Tidak ada perubahan untuk disimpan. Isi foto baru dan/atau password baru terlebih dahulu.');
        }

        $user->update($changes);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile berhasil diperbarui.');
    }

    /**
     * Simpan file foto profil ke public/image/user, mengikuti pola yang sama
     * dengan StudentController::storeImage().
     */
    private function storeImage($file): string
    {
        $destination = public_path('image/user');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $filename = uniqid('user_') . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'image/user/' . $filename;
    }

    private function deleteImage(string $path): void
    {
        $fullPath = public_path($path);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}
