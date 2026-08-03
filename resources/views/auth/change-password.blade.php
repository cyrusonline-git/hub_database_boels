@extends('layouts.app')
@section('title','Wachtwoord wijzigen')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4 mt-4">
            @include('partials.core-logo', ['size' => 36])
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-1">Wachtwoord wijzigen</h5>
                <p class="text-muted small">Je nieuwe wachtwoord geldt direct voor alle apps.</p>

                <form method="POST" action="{{ route('password.change.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Huidig wachtwoord</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autofocus>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nieuw wachtwoord</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Minimaal 8 tekens.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Herhaal nieuw wachtwoord</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Wachtwoord opslaan</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('launcher') }}" class="small">Terug naar het dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
