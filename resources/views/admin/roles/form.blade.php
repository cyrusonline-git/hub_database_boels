@extends('layouts.app')
@section('title', $role->exists ? 'Rol bewerken' : 'Nieuwe rol')

@section('content')
<h3 class="mb-3">{{ $role->exists ? 'Rol bewerken' : 'Nieuwe rol' }}</h3>

<form action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST" class="card p-4">
    @csrf
    @if($role->exists) @method('PUT') @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control" required>
            <small class="text-muted">Bijv. "Fleet Manager", "Project Manager", "Monteur Zuid".</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Applicatie</label>
            <select name="application_id" class="form-select @error('application_id') is-invalid @enderror">
                <option value="">— Platform-breed (alle apps) —</option>
                @foreach($apps as $a)
                    <option value="{{ $a->id }}" @selected(old('application_id', $role->application_id)==$a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Rol geldt alleen binnen deze app. Elke app kan dus eigen rollen hebben.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Beschrijving</label>
            <input type="text" name="description" value="{{ old('description', $role->description) }}" class="form-control">
        </div>
    </div>

    <h5 class="mt-3 mb-2">Dashboard / Launcher</h5>
    <p class="text-muted small">Welke apps ziet iemand met deze rol op zijn dashboard na het inloggen?</p>
    <div class="border rounded p-3 mb-3">
        <div class="row">
            @foreach($apps as $a)
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="launcher_apps[]" value="{{ $a->id }}" id="la{{ $a->id }}"
                            class="form-check-input"
                            @checked(in_array($a->id, old('launcher_apps', $role->exists ? $role->launcherApplications->pluck('id')->all() : [])))>
                        <label for="la{{ $a->id }}" class="form-check-label">{{ $a->name }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <small class="text-muted">Let op: area/depot/land-beperkingen van een app blijven ook gelden.</small>
    </div>

    <h5 class="mt-3 mb-2">Permissies <small class="text-muted fw-normal">(optioneel — geavanceerd)</small></h5>
    <p class="text-muted small">
        Alleen nodig als een app fijnmaziger rechten kent dan de rol zelf (bv. "mag exporteren").
        Voor de meeste apps kun je dit leeg laten — de rol is dan genoeg.
    </p>

    @php $grouped = $permissions->groupBy(fn($p)=>$p->application?->name ?? 'Platform'); @endphp
    @foreach($grouped as $appName => $perms)
        <div class="border rounded p-3 mb-2">
            <h6 class="mb-2">{{ $appName }}</h6>
            <div class="row">
                @foreach($perms as $p)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $p->id }}" id="p{{ $p->id }}"
                                class="form-check-input"
                                @checked(in_array($p->id, old('permissions', $role->permissions->pluck('id')->all())))>
                            <label for="p{{ $p->id }}" class="form-check-label">
                                {{ $p->name }} <small class="text-muted">— <code>{{ $p->key }}</code></small>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="mt-3">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
