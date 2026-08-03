@extends('layouts.app')
@section('title','Inloggen')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4 mt-5">
            <div class="mb-3">
                @include('partials.core-logo', ['size' => 52])
            </div>
            <p class="text-muted small mb-1" style="letter-spacing:3px; text-transform:uppercase;">één login · alle applicaties</p>
            <img src="{{ asset('images/boels-industrial-logo.jpg') }}" alt="Boels Industrial"
                 style="width:120px; border-radius:8px; margin-top:10px;">
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Inloggen</h5>

                <form method="POST" action="{{ url('/login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">E-mailadres</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wachtwoord</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label class="form-check-label" for="remember">Aangemeld blijven</label>
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Inloggen</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="small">Wachtwoord vergeten?</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
