@extends('layouts.app')
@section('title', $application->exists ? 'Applicatie bewerken' : 'Nieuwe applicatie')

@section('content')
<h3 class="mb-3">{{ $application->exists ? 'Applicatie bewerken' : 'Nieuwe applicatie' }}</h3>

<form action="{{ $application->exists ? route('admin.applications.update',$application) : route('admin.applications.store') }}" method="POST" class="card p-4">
    @csrf @if($application->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $application->name) }}" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $application->slug) }}" class="form-control" placeholder="auto bij leeg laten">
        </div>
        <div class="col-12">
            <label class="form-label">Beschrijving</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $application->description) }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">URL</label>
            <input type="url" name="url" value="{{ old('url', $application->url) }}" class="form-control" placeholder="https://fleet.sorai.nl">
        </div>
        <div class="col-md-6">
            <label class="form-label">Sync-sleutel <small class="text-muted">(voor gebruikers-import)</small></label>
            <input type="text" name="sync_key" value="{{ old('sync_key', $application->sync_key) }}" class="form-control" placeholder="zelfde sleutel als in core-users.php van de app">
            <small class="text-muted">Alleen nodig als de app ook zijn gebruikerslijst deelt (core-users.php). De rollenlijst werkt zonder sleutel.</small>
        </div>
        <div class="col-md-3">
            <label class="form-label">Icoon (Bootstrap Icons)</label>
            <input type="text" name="icon" value="{{ old('icon', $application->icon) }}" class="form-control" placeholder="bi-truck">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kleur</label>
            <input type="text" name="color" value="{{ old('color', $application->color) }}" class="form-control" placeholder="#FF6600">
        </div>
        <div class="col-md-3">
            <label class="form-label">Volgorde</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $application->sort_order ?? 0) }}" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" id="active" class="form-check-input" @checked(old('active', $application->active ?? true))>
                <label for="active" class="form-check-label">Actief</label>
            </div>
        </div>
    </div>

    <hr class="my-4">
    <h6 class="text-boels"><i class="bi bi-shield-lock"></i> Toegangsrestricties</h6>
    <p class="text-muted small">
        Leeg = alle areas/depots/countries mogen.
        Niet-leeg = alleen users met overlap zien deze app in de Launcher.
        Bypass via <code>{{ $application->slug ?: '{slug}' }}.global</code> permissie.
    </p>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Restricted to areas</label>
            <input type="text" name="restricted_to_areas" class="form-control" placeholder="bv. Zuid, Noord"
                value="{{ old('restricted_to_areas', is_array($application->restricted_to_areas) ? implode(', ', $application->restricted_to_areas) : '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Restricted to depots</label>
            <input type="text" name="restricted_to_depots" class="form-control" placeholder="bv. Geleen; Industrial"
                value="{{ old('restricted_to_depots', is_array($application->restricted_to_depots) ? implode(', ', $application->restricted_to_depots) : '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Restricted to countries</label>
            <input type="text" name="restricted_to_countries" class="form-control" placeholder="bv. Nederland"
                value="{{ old('restricted_to_countries', is_array($application->restricted_to_countries) ? implode(', ', $application->restricted_to_countries) : '') }}">
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>

@if($application->exists)
    <div class="card p-4 mt-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="mb-1">Rollen in deze app <small class="text-muted">(voor Boels-medewerkers)</small></h5>
                <p class="text-muted small mb-2">
                    De functionele rollen zoals de app ze gebruikt (bv. monteur, expeditie, binnendienst).
                    Ken ze toe aan gebruikers via Beheer → Gebruikers; de app haalt ze op via
                    <code>/api/access/{{ $application->slug }}</code>. Een nieuwe rol ziet deze app
                    standaard ook in de launcher.
                </p>
            </div>
            <div class="text-end">
                <form method="POST" action="{{ route('admin.applications.import-roles', $application) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-secondary" title="Haalt de rollenlijst op die de app publiceert op {{ rtrim($application->url ?? '', '/') }}/core-roles.php">
                        <i class="bi bi-cloud-download"></i> Importeer rollen
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.applications.import-users', $application) }}" class="d-inline"
                      onsubmit="return confirm('Dit haalt de gebruikers van de app op en:\n• koppelt bestaande CORE-logins aan hun app-rol\n• maakt logins aan voor app-gebruikers die in de medewerkerslijst staan — die krijgen DIRECT een activatiemail!\n\nBedoeld voor het aankoppelen/uitrollen. Nogmaals draaien kan rollen terugzetten die je in CORE al had ingetrokken.\n\nDoorgaan?');">
                    @csrf
                    <button class="btn btn-outline-secondary" title="Eenmalige overname van wie welke rol heeft (core-users.php + sync-sleutel)">
                        <i class="bi bi-people"></i> Neem gebruikers over (eenmalig)
                    </button>
                </form>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif

        <table class="table table-sm align-middle mb-3">
            <thead class="table-light"><tr><th>Rol</th><th>Slug</th><th class="text-end">Gebruikers</th><th></th></tr></thead>
            <tbody>
                @forelse($appRoles as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong></td>
                        <td><code>{{ $r->slug }}</code></td>
                        <td class="text-end">{{ $r->users_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.roles.destroy', $r) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Rol {{ $r->name }} verwijderen? Gebruikers verliezen deze rol.');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Nog geen rollen voor deze app.</td></tr>
                @endforelse
            </tbody>
        </table>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="row g-2">
            @csrf
            <input type="hidden" name="application_id" value="{{ $application->id }}">
            <input type="hidden" name="launcher_apps[]" value="{{ $application->id }}">
            <input type="hidden" name="return_to" value="{{ route('admin.applications.edit', $application) }}">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Nieuwe rol, bv. Monteur" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="description" class="form-control" placeholder="Omschrijving (optioneel)">
            </div>
            <div class="col-md-3">
                <button class="btn btn-boels w-100"><i class="bi bi-plus-lg"></i> Rol toevoegen</button>
            </div>
        </form>
    </div>
@endif
@endsection
