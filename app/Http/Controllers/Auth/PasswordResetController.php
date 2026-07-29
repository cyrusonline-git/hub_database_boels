<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /** Reset-link geldigheid in minuten */
    private const TOKEN_TTL_MINUTES = 60;

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        // Alleen mailen voor bestaande, niet-uitgeschakelde accounts.
        // Naar buiten toe altijd dezelfde melding, zodat niet te raden is
        // welke e-mailadressen een account hebben.
        if ($user && $user->status !== User::STATUS_DISABLED) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => hash('sha256', $token), 'created_at' => now()],
            );

            $url = url('/wachtwoord-reset/'.$token.'?email='.urlencode($user->email));
            Mail::to($user->email)->send(new PasswordResetMail($user, $url));
        }

        return back()->with('status',
            'Als dit e-mailadres bij ons bekend is, hebben we een reset-link gestuurd. Check ook je spam-map.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($request->input('email')));

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        $valid = $row
            && hash_equals($row->token, hash('sha256', $request->input('token')))
            && \Illuminate\Support\Carbon::parse($row->created_at)
                ->addMinutes(self::TOKEN_TTL_MINUTES)->isFuture();

        if (! $valid) {
            throw ValidationException::withMessages([
                'email' => __('Deze reset-link is ongeldig of verlopen. Vraag een nieuwe aan.'),
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user || $user->status === User::STATUS_DISABLED) {
            throw ValidationException::withMessages([
                'email' => __('Dit account is uitgeschakeld. Neem contact op met de beheerder.'),
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'status' => User::STATUS_ACTIVE,
            'active' => true,
            'activation_token' => null,
            'activation_token_expires_at' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/launcher')->with('status', 'Je wachtwoord is gewijzigd. Welkom terug!');
    }
}
