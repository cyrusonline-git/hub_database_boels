<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Je huidige wachtwoord klopt niet.',
            'password.different' => 'Het nieuwe wachtwoord moet anders zijn dan het huidige.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        return redirect()->route('launcher')
            ->with('status', 'Je wachtwoord is gewijzigd. Dit geldt direct voor alle apps.');
    }
}
