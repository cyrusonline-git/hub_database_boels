@extends('layouts.app')
@section('title','Subgroep '.$subgroup->subgroup_number)

@section('content')
<nav class="mb-2 small">
    <a href="{{ route('admin.material.index') }}">Materieel</a>
    @if($subgroup->group?->analysis_group)
        &rsaquo; <a href="{{ route('admin.material.groups', ['analysis' => $subgroup->group->analysis_group]) }}">{{ $subgroup->group->analysis_group }}</a>
    @endif
    @if($subgroup->group)
        &rsaquo; <a href="{{ route('admin.material.subgroups', ['group' => $subgroup->group->id]) }}">{{ $subgroup->group->group_name }}</a>
    @endif
    &rsaquo; <strong>{{ $subgroup->subgroup_number }}</strong>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>
        <i class="bi bi-box-seam text-boels"></i>
        {{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}
    </h3>
    @if($subgroup->group)
        <a href="{{ route('admin.material.subgroups', ['group' => $subgroup->group->id]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ $subgroup->group->group_name }}
        </a>
    @endif
</div>

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

        <div class="card">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Unieke machines <span class="badge" style="background:#FF6600;">{{ $machines->total() }}</span></span>
                <form method="GET" class="d-flex" style="max-width:220px;">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Zoek nummer...">
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th class="ps-3">Materieelnummer</th><th>Omschrijving</th><th></th></tr></thead>
                    <tbody>
                        @forelse($machines as $m)
                            <tr>
                                <td class="ps-3"><strong>{{ $m->machine_number }}</strong></td>
                                <td>{{ $m->description }}</td>
                                <td class="text-end pe-2">
                                    <a href="{{ route('admin.material.machine', $m) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Geen machines in deze subgroep.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($machines->hasPages())
                <div class="card-body py-2">{{ $machines->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-lg-7">
        @include('admin.material._specs', ['subgroup' => $subgroup])
    </div>
</div>
@endsection
