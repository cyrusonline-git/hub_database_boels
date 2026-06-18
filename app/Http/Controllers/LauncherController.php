<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LauncherController extends Controller
{
    public function index(Request $request)
    {
        // applications() retourneert al een gefilterde Collection
        $apps = $request->user()->applications();

        return view('launcher.index', compact('apps'));
    }
}
