@extends('layouts.app')
@section('title','Activeer account')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4 mt-5">
            <div class="boels-logo mx-auto" style="width:64px;height:64px;font-size:38px;">B</div>
            <h3 class="mt-3 mb-0">{{ config('boels.brand.name') }}</h3>
            <p class="text-muted">{{ config('boels.brand.product') }}</p>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title">Account activeren</h5>
                <p class="text-muted small">Hallo {{ $user->name }} — kies hieronder je wachtwoord. Je wordt daarna automatisch ingelogd.</p>

                <form method="POST" action="{{ url('/activate/'.$token) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">E-mailadres</label>
                        <input type="email" value="{{ $user->email }}" class="form-control" disabled readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wachtwoord (min. 8 tekens) *</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wachtwoord herhalen *</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Activeer en log in</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
