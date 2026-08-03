@extends('layouts.app')
@section('title','Wachtwoord vergeten')

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
                <h5 class="card-title mb-3">Wachtwoord vergeten</h5>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <p class="text-muted small">
                    Vul je e-mailadres in. Als het adres bij ons bekend is, sturen we je
                    een link waarmee je een nieuw wachtwoord kunt instellen.
                </p>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">E-mailadres</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Verstuur reset-link</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="small">Terug naar inloggen</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
