@extends('layouts.app')
@section('title','Nieuw wachtwoord instellen')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4 mt-5">
            <div class="mb-3">
                @include('partials.core-logo', ['size' => 52])
            </div>
            <p class="text-muted mb-1" style="letter-spacing:2.5px; text-transform:uppercase; font-size:11px;">Powered by</p>
            <img src="{{ asset('images/boels-industrial-logo.jpg') }}" alt="Boels Industrial"
                 style="width:110px; border-radius:8px;">
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
