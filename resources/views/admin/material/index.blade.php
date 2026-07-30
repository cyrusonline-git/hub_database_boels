@extends('layouts.app')
@section('title','Materieel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-truck text-boels"></i> Materieel
        <small class="text-muted">({{ $subgroups->total() }} subgroepen)</small>
    </h3>
    <div>
        <a href="{{ route('admin.material.machines') }}" class="btn btn-outline-secondary">
            <i class="bi bi-search"></i> Zoek uniek nummer
        </a>
        <a href="{{ route('admin.material-imports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-upload"></i> Uploads
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ number_format($stats['machines'],0,',','.') }}</div><div class="text-muted small">Unieke nummers</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['subgroups'] }}</div><div class="text-muted small">Subgroepen</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['withSpecs'] }}</div><div class="text-muted small">Met specificaties</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['groups'] }}</div><div class="text-muted small">Productgroepen</div></div></div>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Zoek subgroep / naam / merk / type">
        </div>
        <div class="col-md-3">
            <select name="analysis" class="form-select">
                <option value="">— Analysegroep —</option>
                @foreach($analysisGroups as $ag)
                    <option value="{{ $ag }}" @selected(request('analysis')===$ag)>{{ $ag }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="group" class="form-select">
                <option value="">— Productgroep —</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" @selected(request('group')==$g->id)>{{ $g->group_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="specs" class="form-select">
                <option value="">— Specs —</option>
                <option value="met" @selected(request('specs')==='met')>Met specificaties</option>
                <option value="zonder" @selected(request('specs')==='zonder')>Zonder specificaties</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-boels w-100"><i class="bi bi-funnel"></i></button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Subgroep</th>
                    <th>Naam</th>
                    <th>Merk / Type</th>
                    <th>Productgroep</th>
                    <th>Analysegroep</th>
                    <th class="text-end"># Machines</th>
                    <th class="text-center">Specs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subgroups as $sg)
                    <tr>
                        <td><strong>{{ $sg->subgroup_number }}</strong></td>
                        <td>{{ $sg->subgroup_name }}</td>
                        <td class="text-muted">{{ trim(($sg->merk ?? '').' '.($sg->type ?? '')) ?: '—' }}</td>
                        <td>{{ $sg->group?->group_name }}</td>
                        <td class="text-muted">{{ $sg->group?->analysis_group ?? '—' }}</td>
                        <td class="text-end">{{ number_format($sg->machines_count,0,',','.') }}</td>
                        <td class="text-center">
                            @if($sg->specifications)
                                <span class="badge bg-success">{{ count($sg->specifications) }}</span>
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.material.show', $sg) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> Bekijk
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Geen subgroepen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $subgroups->links() }}</div>
@endsection
