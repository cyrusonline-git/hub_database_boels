<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ActivationController extends Controller
{
    public function show(string $token)
    {
        $user = $this->findUserByToken($token);
        return view('auth.activate', compact('user', 'token'));
    }

    public function activate(Request $request, string $token)
    {
        $user = $this->findUserByToken($token);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'status' => User::STATUS_ACTIVE,
            'active' => true,
            'activation_token' => null,
            'activation_token_expires_at' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/launcher')->with('status', 'Welkom! Je account is geactiveerd.');
    }

    private function findUserByToken(string $token): User
    {
        $user = User::where('activation_token', $token)->first();
        abort_unless($user, 404, 'Activatie-link niet gevonden of al gebruikt.');
        abort_if(
            $user->activation_token_expires_at && $user->activation_token_expires_at->isPast(),
            410,
            'Deze activatie-link is verlopen. Vraag een nieuwe aan bij je beheerder.'
        );
        return $user;
    }
}
