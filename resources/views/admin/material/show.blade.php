@extends('layouts.app')
@section('title','Subgroep '.$subgroup->subgroup_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>
        <i class="bi bi-box-seam text-boels"></i>
        {{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}
    </h3>
    <a href="{{ route('admin.material.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Terug naar overzicht
    </a>
</div>

<nav class="mb-3 text-muted small">
    {{ $subgroup->group?->analysis_group ?? 'Analysegroep onbekend' }}
    &rsaquo; {{ $subgroup->group?->group_name }}
    &rsaquo; <strong>{{ $subgroup->subgroup_number }}</strong>
</nav>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header fw-bold">Algemeen</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Subgroepnummer</th><td>{{ $subgroup->subgroup_number }}</td></tr>
                        <tr><th class="ps-3">Omschrijving</th><td>{{ $subgroup->subgroup_name }}</td></tr>
                        <tr><th class="ps-3">Merk</th><td>{{ $subgroup->merk ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Type</th><td>{{ $subgroup->type ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Productgroep</th><td>{{ $subgroup->group?->group_name }}</td></tr>
                        <tr><th class="ps-3">Analysegroep</th><td>{{ $subgroup->group?->analysis_group ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Categorie</th><td>{{ $subgroup->categorie ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Toepassing</th><td>{{ $subgroup->toepassing ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Tabblad</th><td>{{ $subgroup->tabblad ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Service codes</th><td>{{ $subgroup->service_codes ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if($subgroup->highlights)
            <div class="card mb-3">
                <div class="card-header fw-bold">Highlights</div>
                <ul class="list-group list-group-flush">
                    @foreach($subgroup->highlights as $h)<li class="list-group-item">{{ $h }}</li>@endforeach
                </ul>
            </div>
        @endif

        @foreach(['accessoires' => 'Accessoires', 'verkoopartikelen' => 'Verkoopartikelen', 'alternatieven' => 'Alternatieven'] as $field => $label)
            @if($subgroup->$field)
                <div class="card mb-3">
                    <div class="card-header fw-bold">{{ $label }}</div>
                    <ul class="list-group list-group-flush">
                        @foreach($subgroup->$field as $item)<li class="list-group-item">{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header fw-bold">
                Specificaties
                @if($subgroup->specifications)<span class="badge bg-success">{{ count($subgroup->specifications) }}</span>@endif
            </div>
            @if($subgroup->specifications)
                <div class="card-body p-0" style="max-height:420px; overflow-y:auto;">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            @foreach($subgroup->specifications as $key => $value)
                                <tr><th class="ps-3" style="width:45%">{{ $key }}</th><td>{{ $value }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body text-muted">
                    Geen specificaties — deze subgroep staat (nog) niet in de subgroeplijst.
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Unieke machines <span class="badge bg-boels" style="background:#FF6600;">{{ $machines->total() }}</span></span>
                <form method="GET" class="d-flex" style="max-width:260px;">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Zoek nummer...">
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th class="ps-3">Materieelnummer</th><th>Omschrijving</th></tr></thead>
                    <tbody>
                        @forelse($machines as $m)
                            <tr>
                                <td class="ps-3"><strong>{{ $m->machine_number }}</strong></td>
                                <td>{{ $m->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Geen machines in deze subgroep.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($machines->hasPages())
                <div class="card-body py-2">{{ $machines->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
