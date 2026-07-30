@extends('layouts.app')
@section('title','Zoek materieelnummer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-search text-boels"></i> Uniek materieelnummer zoeken
        <small class="text-muted">({{ number_format($machines->total(),0,',','.') }})</small>
    </h3>
    <a href="{{ route('admin.material.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Materieel overzicht
    </a>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Zoek op materieelnummer of omschrijving" autofocus>
        </div>
        <div class="col-md-2">
            <button class="btn btn-boels w-100"><i class="bi bi-search"></i> Zoek</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Materieelnummer</th>
                    <th>Omschrijving</th>
                    <th>Subgroep</th>
                    <th>Productgroep</th>
                    <th>Analysegroep</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($machines as $m)
                    <tr>
                        <td><strong>{{ $m->machine_number }}</strong></td>
                        <td>{{ $m->description }}</td>
                        <td>{{ $m->subgroup?->subgroup_number }}</td>
                        <td>{{ $m->subgroup?->group?->group_name }}</td>
                        <td class="text-muted">{{ $m->subgroup?->group?->analysis_group ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.material.machine', $m) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-seam"></i> Specs
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Geen machines gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $machines->links() }}</div>
@endsection
