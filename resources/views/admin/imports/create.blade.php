@extends('layouts.app')
@section('title','Nieuwe import')

@section('content')
<h3 class="mb-3"><i class="bi bi-upload text-boels"></i> Nieuwe import</h3>

<form action="{{ route('admin.imports.store') }}" method="POST" enctype="multipart/form-data" class="card p-4">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Entiteit *</label>
            <select name="entity" class="form-select" required>
                @foreach($entities as $key => $config)
                    <option value="{{ $key }}">{{ $config['label'] }}</option>
                @endforeach
            </select>
            <small class="text-muted">Selecteer welke soort data je gaat importeren.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Bestand (xlsx, xls, csv) *</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
            <small class="text-muted">Max {{ config('boels.import.max_file_size_mb') }} MB.</small>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-boels">Upload & Mapping</button>
        <a href="{{ route('admin.imports.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
