@extends('layouts.app')
@section('title','Rollen')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-shield-lock text-boels"></i> Rollen</h3>
    <div class="d-flex gap-2">
        <form method="GET">
            <select name="app" class="form-select" onchange="this.form.submit()">
                <option value="">— Alle apps —</option>
                <option value="platform" @selected(request('app')==='platform')>Platform-breed</option>
                @foreach($apps as $a)
                    <option value="{{ $a->id }}" @selected(request('app')==(string)$a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe rol</a>
    </div>
</div>

<div class="alert alert-light border small mb-3">
    <strong>Hoe zit het in elkaar?</strong>
    Een <strong>rol</strong> is de functie van een medewerker binnen één app (bv. "Monteur" in de Scanner App)
    of platform-breed (bv. "Administrator"). Hier in CORE regel je alleen <em>wie welke rol heeft</em> en
    <em>welke app-tegels iemand daardoor op zijn dashboard ziet</em>. De app leest vervolgens uit welke rol
    de ingelogde medewerker heeft — <em>wat die rol precies mag, is in de app zelf gedefinieerd</em>.
    <strong>Permissies</strong> zijn optioneel fijnmazig — losse rechten zoals "mag exporteren"; de meeste apps
    hebben genoeg aan alleen rollen.
    <br><strong>Tip:</strong> app-rollen beheer je het makkelijkst op de app zelf:
    Beheer → Applicaties → app openen → blok "Rollen in deze app" (met importknop).
</div>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr><th>Naam</th><th>Applicatie</th><th>Slug</th><th>Permissies</th><th>Gebruikers</th><th>Systeem</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($roles as $r)
            <tr>
                <td><strong>{{ $r->name }}</strong><br><small class="text-muted">{{ $r->description }}</small></td>
                <td>
                    @if($r->application)
                        <span class="badge" style="background:#FF6600;">{{ $r->application->name }}</span>
                    @else
                        <span class="badge bg-secondary">platform</span>
                    @endif
                </td>
                <td><code>{{ $r->slug }}</code></td>
                <td>{{ $r->permissions_count }}</td>
                <td>{{ $r->users_count }}</td>
                <td>{!! $r->is_system ? '<span class="badge bg-secondary">systeem</span>' : '' !!}</td>
                <td class="text-end">
                    <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    @unless($r->is_system)
                    <form action="{{ route('admin.roles.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
