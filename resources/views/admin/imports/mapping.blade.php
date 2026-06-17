@extends('layouts.app')
@section('title','Mapping')

@section('content')
<h3 class="mb-3"><i class="bi bi-arrow-left-right text-boels"></i> Mapping — {{ $config['label'] }}</h3>

<div class="alert alert-info">
    Bestand: <strong>{{ $job->original_filename }}</strong> &middot; {{ $total_rows }} datarijen gevonden.
    Onbekende koppen kun je hieronder mappen — opgeslagen mappings worden voortaan automatisch toegepast.
</div>

<form action="{{ route('admin.imports.storeMapping', $job) }}" method="POST" class="card p-4">
    @csrf
    <table class="table align-middle">
        <thead>
            <tr><th style="width:30%">Kolom in bestand</th><th style="width:40%">Mappen naar veld</th><th>Voorbeeldwaarde</th></tr>
        </thead>
        <tbody>
        @foreach($headers as $i => $header)
            <tr>
                <td><code>{{ $header }}</code></td>
                <td>
                    <select name="mapping[{{ $header }}]" class="form-select">
                        <option value="">— niet importeren —</option>
                        @foreach($config['fields'] as $key => $f)
                            <option value="{{ $key }}" @selected(($suggested[$header] ?? null) === $key)>
                                {{ $f['label'] }} ({{ $key }}) {{ ($f['required'] ?? false) ? '*' : '' }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td class="text-muted small">{{ $preview[0][$i] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="border rounded p-3 mt-4 bg-light">
        <div class="form-check">
            <input type="hidden" name="sync_mode" value="0">
            <input type="checkbox" name="sync_mode" value="1" id="sync_mode" class="form-check-input">
            <label for="sync_mode" class="form-check-label">
                <strong>Sync-modus aan</strong> &mdash;
                {{ $config['label'] }} die NIET in dit bestand staan worden op
                <em>inactief</em> gezet (active = 0 + soft-delete).
            </label>
            <div class="form-text">
                Bij volgende import waar ze wel weer in staan, worden ze automatisch
                opnieuw actief. Veilig: data verdwijnt niet uit de database, alleen
                gemarkeerd als inactief.
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-boels">Mapping opslaan</button>
        <a href="{{ route('admin.imports.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
