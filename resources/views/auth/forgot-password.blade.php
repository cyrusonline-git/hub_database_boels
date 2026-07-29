@extends('layouts.app')
@section('title','Wachtwoord vergeten')

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
