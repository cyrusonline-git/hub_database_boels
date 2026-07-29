@extends('layouts.app')
@section('title','Nieuw wachtwoord instellen')

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
                <h5 class="card-title mb-3">Nieuw wachtwoord instellen</h5>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="mb-3">
                        <label class="form-label">E-mailadres</label>
                        <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nieuw wachtwoord</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Minimaal 8 tekens.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Herhaal nieuw wachtwoord</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Wachtwoord opslaan</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="small">Terug naar inloggen</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
