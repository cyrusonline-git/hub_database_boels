@extends('layouts.app')
@section('title', $permission->exists ? 'Permissie bewerken' : 'Nieuwe permissie')

@section('content')
<h3 class="mb-3">{{ $permission->exists ? 'Permissie bewerken' : 'Nieuwe permissie' }}</h3>
<form action="{{ $permission->exists ? route('admin.permissions.update',$permission) : route('admin.permissions.store') }}" method="POST" class="card p-4">
    @csrf @if($permission->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Applicatie</label>
            <select name="application_id" class="form-select">
                <option value="">— platform-wide —</option>
                @foreach($applications as $a)
                    <option value="{{ $a->id }}" @selected(old('application_id', $permission->application_id) == $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Key *</label>
            <input type="text" name="key" value="{{ old('key', $permission->key) }}" class="form-control" placeholder="bv. fleet.machines.edit" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $permission->name) }}" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Beschrijving</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $permission->description) }}</textarea>
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
