@extends('layouts.app')
@section('title','Materieel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-truck text-boels"></i> Materieel</h3>
    <div>
        <a href="{{ route('admin.material.machines') }}" class="btn btn-outline-secondary">
            <i class="bi bi-search"></i> Zoek uniek nummer
        </a>
        <a href="{{ route('admin.material.subgroups') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list-ul"></i> Alle subgroepen
        </a>
        <a href="{{ route('admin.material-imports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-upload"></i> Uploads
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ number_format($stats['machines'],0,',','.') }}</div><div class="text-muted small">Unieke nummers</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['subgroups'] }}</div><div class="text-muted small">Subgroepen</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['withSpecs'] }}</div><div class="text-muted small">Met specificaties</div></div></div>
    <div class="col-md-3"><div class="card text-center p-2"><div class="fs-4 fw-bold">{{ $stats['groups'] }}</div><div class="text-muted small">Productgroepen</div></div></div>
</div>

<h5 class="mb-3 text-muted">Kies een analysegroep om door te klikken</h5>

<div class="row g-3">
    @foreach($analysisGroups as $ag)
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.material.groups', ['analysis' => $ag->analysis_group]) }}"
               class="card h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="fw-bold mb-2"><i class="bi bi-folder text-boels"></i> {{ $ag->analysis_group }}</div>
                    <div class="small text-muted">
                        {{ $ag->groups_count }} productgroep(en) ·
                        {{ $ag->subgroups_count }} subgroepen<br>
                        {{ number_format($ag->machines_count,0,',','.') }} unieke machines
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
