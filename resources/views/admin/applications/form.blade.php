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
@endsection
