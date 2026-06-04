@extends('layouts.app')
@section('title', $field->exists ? 'Custom Field bewerken' : 'Nieuw custom field')

@section('content')
<h3 class="mb-3">{{ $field->exists ? 'Bewerken' : 'Nieuw custom field' }}</h3>
<form action="{{ $field->exists ? route('admin.custom-fields.update',$field) : route('admin.custom-fields.store') }}" method="POST" class="card p-4">
    @csrf @if($field->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Entiteit *</label>
            <select name="entity" class="form-select">
                @foreach(['customer','machine','project','employee','damage','work_order'] as $e)
                    <option value="{{ $e }}" @selected(old('entity',$field->entity)===$e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Key *</label>
            <input type="text" name="key" value="{{ old('key', $field->key) }}" class="form-control" placeholder="bv. atex_certification" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Label *</label>
            <input type="text" name="label" value="{{ old('label', $field->label) }}" class="form-control" placeholder="ATEX certificering" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Type *</label>
            <select name="type" class="form-select">
                @foreach(['text','number','date','boolean','select'] as $t)
                    <option value="{{ $t }}" @selected(old('type',$field->type)===$t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label">Opties (alleen voor type "select", één per regel)</label>
            <textarea name="options_raw" class="form-control" rows="3">{{ old('options_raw', is_array($field->options) ? implode("\n", $field->options) : '') }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Volgorde</label>
            <input type="number" name="sort_order" value="{{ old('sort_order',$field->sort_order ?? 0) }}" class="form-control">
            <div class="form-check mt-2">
                <input type="hidden" name="required" value="0">
                <input type="checkbox" name="required" value="1" id="req" class="form-check-input" @checked(old('required',$field->required))>
                <label for="req" class="form-check-label">Verplicht</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.custom-fields.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
