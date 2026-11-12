@extends('layouts.app')
@section('title','SAP-codes → medewerkers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-person-badge text-boels"></i> SAP-codes → medewerkers</h3>
    <a href="{{ route('admin.sap-uploads.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Uploads SAP</a>
</div>
@if (session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
<p class="text-muted">
    SAP schrijft de aanmaker als code: de eerste vijf letters van de achternaam (zonder tussenvoegsel) plus de eerste letter
    van de voornaam — <em>Wouter Zwackhalen</em> = <code>ZWACKW</code>. CORE koppelt automatisch als precies één medewerker
    past; de rest koppel je hier eenmalig. Namen die SAP voluit schrijft (planning) staan als <code>NAAM:…</code>.
</p>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Code / naam in SAP</th><th>Gezien</th><th>Medewerker in CORE</th><th>Bron</th><th style="width:360px">Koppelen</th></tr></thead>
            <tbody>
            @foreach($rijen as $c)
                <tr class="{{ $c->employee_id ? '' : 'table-warning' }}">
                    <td><code>{{ $c->code }}</code>@if($c->weergave && $c->weergave !== $c->code)<div class="small text-muted">{{ $c->weergave }}</div>@endif</td>
                    <td class="small text-muted">{{ $c->gezien }}× · {{ $c->laatst_gezien_at?->format('d-m-Y') }}</td>
                    <td>{{ $c->employee?->name ?? '—' }}</td>
                    <td><span class="badge {{ $c->bron === 'handmatig' ? 'bg-primary' : ($c->bron === 'auto' ? 'bg-success' : 'bg-warning text-dark') }}">{{ $c->bron }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.sap-uploads.koppel', $c) }}" class="d-flex gap-1">
                            @csrf
                            <select name="employee_id" class="form-select form-select-sm">
                                <option value="">— niet gekoppeld —</option>
                                @if($c->kandidaten->isNotEmpty())
                                    <optgroup label="Suggesties">
                                        @foreach($c->kandidaten as $k)<option value="{{ $k['id'] }}">{{ $k['naam'] }} ({{ $k['code'] }})</option>@endforeach
                                    </optgroup>
                                @endif
                                <optgroup label="Alle medewerkers">
                                    @foreach($medewerkers as $m)<option value="{{ $m->id }}" @selected($c->employee_id === $m->id)>{{ $m->name }}</option>@endforeach
                                </optgroup>
                            </select>
                            <button class="btn btn-boels btn-sm">Opslaan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
