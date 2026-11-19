<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        $response = response()->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        // Oude host-only cookies (van vóór SESSION_DOMAIN=.sorai.nl) naast de .sorai.nl-variant kunnen een
        // verkeerd token opleveren (419). Laat de host-only versies verlopen; de domeinbrede blijven staan.
        if (config('session.domain')) {
            foreach ([config('session.cookie'), 'XSRF-TOKEN'] as $naam) {
                $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($naam, '', 1, '/', null, (bool) config('session.secure'), $naam !== 'XSRF-TOKEN', false, 'lax'));
            }
        }

        return $response;
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Onjuist e-mailadres of wachtwoord.'),
            ]);
        }

        $user = Auth::user();

        if ($user->status === \App\Models\User::STATUS_PENDING) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Je account is nog niet geactiveerd. Check je mail voor de activatie-link.'),
            ]);
        }

        if ($user->status === \App\Models\User::STATUS_DISABLED || ! $user->active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Dit account is uitgeschakeld. Neem contact op met de beheerder.'),
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended('/launcher');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
