@extends('layouts.app')
@section('title','Inloggen')

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
            </div>
        </div>
    </div>
</div>
@endsection
