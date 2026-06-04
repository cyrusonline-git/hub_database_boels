<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LauncherController extends Controller
{
    public function index(Request $request)
    {
        $apps = $request->user()->applications()->get();

        return view('launcher.index', compact('apps'));
    }
}
