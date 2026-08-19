<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        if ($request->user()->hasVerifiedEmail()) {
            $this->activateIfNeeded($request->user());

            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $this->activateIfNeeded($request->user());

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    private function activateIfNeeded(User $user): void
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            $user->forceFill(['status' => User::STATUS_ACTIVE])->save();
        }
    }
}
