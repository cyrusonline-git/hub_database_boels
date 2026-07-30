@extends('layouts.app')
@section('title','Machine '.$machine->machine_number)

@section('content')
<nav class="mb-2 small">
    <a href="{{ route('admin.material.index') }}">Materieel</a>
    @if($subgroup?->group?->analysis_group)
        &rsaquo; <a href="{{ route('admin.material.groups', ['analysis' => $subgroup->group->analysis_group]) }}">{{ $subgroup->group->analysis_group }}</a>
    @endif
    @if($subgroup?->group)
        &rsaquo; <a href="{{ route('admin.material.subgroups', ['group' => $subgroup->group->id]) }}">{{ $subgroup->group->group_name }}</a>
    @endif
    @if($subgroup)
        &rsaquo; <a href="{{ route('admin.material.show', $subgroup) }}">{{ $subgroup->subgroup_number }}</a>
    @endif
    &rsaquo; <strong>{{ $machine->machine_number }}</strong>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>
        <i class="bi bi-gear-wide-connected text-boels"></i>
        {{ $machine->machine_number }}
        <small class="text-muted">{{ $machine->description }}</small>
    </h3>
    @if($subgroup)
        <a href="{{ route('admin.material.show', $subgroup) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Subgroep {{ $subgroup->subgroup_number }}
        </a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header fw-bold">Deze machine</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Materieelnummer</th><td><strong>{{ $machine->machine_number }}</strong></td></tr>
                        <tr><th class="ps-3">Omschrijving</th><td>{{ $machine->description }}</td></tr>
                        @if($machine->brand)<tr><th class="ps-3">Merk</th><td>{{ $machine->brand }}</td></tr>@endif
                        @if($machine->model)<tr><th class="ps-3">Model</th><td>{{ $machine->model }}</td></tr>@endif
                        @if($machine->serial_number)<tr><th class="ps-3">Serienummer</th><td>{{ $machine->serial_number }}</td></tr>@endif
                        @if($machine->year)<tr><th class="ps-3">Bouwjaar</th><td>{{ $machine->year }}</td></tr>@endif
                        @if($machine->location)<tr><th class="ps-3">Locatie</th><td>{{ $machine->location }}</td></tr>@endif
                        <tr><th class="ps-3">Subgroep</th>
                            <td>
                                @if($subgroup)
                                    <a href="{{ route('admin.material.show', $subgroup) }}">{{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}</a>
                                @else — @endif
                            </td>
                        </tr>
                        <tr><th class="ps-3">Productgroep</th><td>{{ $subgroup?->group?->group_name ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Analysegroep</th><td>{{ $subgroup?->group?->analysis_group ?? '—' }}</td></tr>
                        @if($subgroup?->merk || $subgroup?->type)
                            <tr><th class="ps-3">Merk / Type (subgroep)</th><td>{{ trim(($subgroup->merk ?? '').' '.($subgroup->type ?? '')) }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-muted small">
            <i class="bi bi-info-circle"></i>
            De specificaties hiernaast gelden voor alle machines in subgroep
            {{ $subgroup?->subgroup_number }} en komen uit de subgroeplijst.
        </p>
    </div>

    <div class="col-lg-7">
        @include('admin.material._specs', ['subgroup' => $subgroup])
    </div>
</div>
@endsection
