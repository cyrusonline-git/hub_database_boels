@extends('layouts.app')
@section('title', $user->exists ? 'Gebruiker bewerken' : 'Nieuwe gebruiker')

@section('content')
<h3 class="mb-3">{{ $user->exists ? 'Gebruiker bewerken' : 'Nieuwe gebruiker' }}</h3>

<form action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="card p-4">
    @csrf
    @if($user->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail *</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Wachtwoord {{ $user->exists ? '(leeg laten om te behouden)' : '*' }}</label>
            <input type="password" name="password" class="form-control" minlength="8">
        </div>
        <div class="col-md-6">
            <label class="form-label">Gekoppelde medewerker</label>
            <select name="employee_id" class="form-select">
                <option value="">— geen —</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" @selected(old('employee_id', $user->employee_id) == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <div class="form-check">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="form-check-input" id="active" @checked(old('active', $user->active ?? true))>
                <label for="active" class="form-check-label">Actief</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_super_admin" value="0">
                <input type="checkbox" name="is_super_admin" value="1" class="form-check-input" id="super" @checked(old('is_super_admin', $user->is_super_admin ?? false))>
                <label for="super" class="form-check-label">Super Admin</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Rollen</label>
            <div class="row">
                @foreach($roles as $r)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="roles[]" value="{{ $r->id }}" id="r{{ $r->id }}" class="form-check-input"
                                @checked(in_array($r->id, old('roles', $user->roles->pluck('id')->all())))>
                            <label for="r{{ $r->id }}" class="form-check-label">{{ $r->name }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
