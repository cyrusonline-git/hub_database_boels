@extends('layouts.app')
@section('title', $alias->exists ? 'Alias bewerken' : 'Nieuwe alias')

@section('content')
<h3 class="mb-3">{{ $alias->exists ? 'Alias bewerken' : 'Nieuwe alias' }}</h3>
<form action="{{ $alias->exists ? route('admin.field-aliases.update',$alias) : route('admin.field-aliases.store') }}" method="POST" class="card p-4">
    @csrf @if($alias->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Entiteit *</label>
            <select name="entity" class="form-select">
                @foreach(['customer','machine','project','employee'] as $e)
                    <option value="{{ $e }}" @selected(old('entity',$alias->entity)===$e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Alias (kolomkop in bestand) *</label>
            <input type="text" name="alias" value="{{ old('alias', $alias->alias) }}" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Mappen naar veld *</label>
            <input type="text" name="field" value="{{ old('field', $alias->field) }}" class="form-control" required placeholder="bv. customer_name">
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.field-aliases.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
