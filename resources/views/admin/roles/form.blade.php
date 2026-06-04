@extends('layouts.app')
@section('title', $role->exists ? 'Rol bewerken' : 'Nieuwe rol')

@section('content')
<h3 class="mb-3">{{ $role->exists ? 'Rol bewerken' : 'Nieuwe rol' }}</h3>

<form action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST" class="card p-4">
    @csrf
    @if($role->exists) @method('PUT') @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control" required>
            <small class="text-muted">Bijv. "Fleet Manager", "Project Manager", "Monteur Zuid".</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Beschrijving</label>
            <input type="text" name="description" value="{{ old('description', $role->description) }}" class="form-control">
        </div>
    </div>

    <h5 class="mt-3 mb-2">Permissies</h5>
    <p class="text-muted small">Permissies per applicatie — selecteer welke deze rol mag.</p>

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
