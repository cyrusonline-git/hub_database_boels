<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Boels CORE wordt voorbereid op consumerende apps (Fleet App, Sales App, ...).
// API tokens via Laravel Sanctum (komt in fase identity-provider).

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/applications', function (Request $request) {
    return $request->user()->applications()->get();
});
