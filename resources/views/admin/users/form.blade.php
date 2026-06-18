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
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['active' => 'Actief', 'pending_activation' => 'Wacht op activatie', 'disabled' => 'Uitgeschakeld'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $user->status ?? 'active') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-check mt-2">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="form-check-input" id="active" @checked(old('active', $user->active ?? true))>
                <label for="active" class="form-check-label">Actief (boolean flag)</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_super_admin" value="0">
                <input type="checkbox" name="is_super_admin" value="1" class="form-check-input" id="super" @checked(old('is_super_admin', $user->is_super_admin ?? false))>
                <label for="super" class="form-check-label">Super Admin (ziet altijd alles)</label>
            </div>
        </div>

        <div class="col-12">
            <hr>
            <h6 class="text-boels"><i class="bi bi-geo-alt"></i> Toegangsgebieden (laat leeg om area-scoping uit te schakelen)</h6>
            <p class="text-muted small">Komma-gescheiden lijst. Apps met area-restricties worden alleen getoond als deze user overlap heeft. Apps zonder restrictie zijn altijd zichtbaar.</p>
        </div>

        <div class="col-md-4">
            <label class="form-label">Allowed areas</label>
            <input type="text" name="allowed_areas" class="form-control" placeholder="bv. Noord, Zuid"
                value="{{ old('allowed_areas', is_array($user->allowed_areas) ? implode(', ', $user->allowed_areas) : '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Allowed depots</label>
            <input type="text" name="allowed_depots" class="form-control" placeholder="bv. Geleen; Industrial"
                value="{{ old('allowed_depots', is_array($user->allowed_depots) ? implode(', ', $user->allowed_depots) : '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Allowed countries</label>
            <input type="text" name="allowed_countries" class="form-control" placeholder="bv. Nederland"
                value="{{ old('allowed_countries', is_array($user->allowed_countries) ? implode(', ', $user->allowed_countries) : '') }}">
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
