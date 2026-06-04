@extends('layouts.app')
@section('title','Application Launcher')

@section('content')
<div class="text-center mb-5 mt-3">
    <h2>Welkom, {{ explode(' ', auth()->user()->name)[0] }}</h2>
    <p class="text-muted">Selecteer een applicatie om te starten.</p>
</div>

@if($apps->isEmpty())
    <div class="alert alert-warning text-center">
        Je hebt nog geen toegang tot een applicatie. Neem contact op met een beheerder.
    </div>
@else
<div class="row g-4 justify-content-center">
    @foreach($apps as $app)
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ $app->url ?: '#' }}" target="_blank" class="text-decoration-none">
                <div class="card text-center p-4 app-tile h-100">
                    <div class="icon-circle" style="background: {{ $app->color }}; color: #fff;">
                        <i class="{{ $app->icon ?: 'bi-app' }}"></i>
                    </div>
                    <h5 class="mb-1 text-dark">{{ $app->name }}</h5>
                    <small class="text-muted">{{ $app->description }}</small>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endif
@endsection
