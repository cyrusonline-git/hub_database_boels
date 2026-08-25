@extends('layouts.app')
@section('title','Artikel '.$machine->machine_number)

@section('content')
<div class="mx-auto" style="max-width: 1100px;">
    <nav class="mb-2 small">
        <a href="{{ route('articles.index') }}">Artikelen</a>
        @if($subgroup)
            &rsaquo; <a href="{{ route('articles.subgroup', $subgroup) }}">{{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}</a>
        @endif
        &rsaquo; <strong>{{ $machine->machine_number }}</strong>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">
            <i class="bi bi-gear-wide-connected text-boels"></i>
            {{ $machine->machine_number }}
            <small class="text-muted">{{ $machine->description }}</small>
        </h3>
        <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-search"></i> Nieuw zoeken
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-bold">Dit artikel</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th class="ps-3" style="width:40%">Artikelnummer</th><td><strong>{{ $machine->machine_number }}</strong></td></tr>
                            <tr><th class="ps-3">Omschrijving</th><td>{{ $machine->description }}</td></tr>
                            @if($machine->brand)<tr><th class="ps-3">Merk</th><td>{{ $machine->brand }}</td></tr>@endif
                            @if($machine->model)<tr><th class="ps-3">Model</th><td>{{ $machine->model }}</td></tr>@endif
                            @if($machine->serial_number)<tr><th class="ps-3">Serienummer</th><td>{{ $machine->serial_number }}</td></tr>@endif
                            @if($machine->year)<tr><th class="ps-3">Bouwjaar</th><td>{{ $machine->year }}</td></tr>@endif
                            @if($machine->location)<tr><th class="ps-3">Locatie</th><td>{{ $machine->location }}</td></tr>@endif
                            <tr><th class="ps-3">Producttype</th>
                                <td>
                                    @if($subgroup)
                                        <a href="{{ route('articles.subgroup', $subgroup) }}">{{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}</a>
                                    @else — @endif
                                </td>
                            </tr>
                            <tr><th class="ps-3">Productgroep</th><td>{{ $subgroup?->group?->group_name ?? '—' }}</td></tr>
                            @if($subgroup?->merk || $subgroup?->type)
                                <tr><th class="ps-3">Merk / Type</th><td>{{ trim(($subgroup->merk ?? '').' '.($subgroup->type ?? '')) }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-muted small">
                <i class="bi bi-info-circle"></i>
                De specificaties gelden voor alle artikelen van producttype
                {{ $subgroup?->subgroup_number ?? '—' }} en komen uit de geüploade materieellijst.
            </p>
        </div>

        <div class="col-lg-7">
            @include('admin.material._specs', ['subgroup' => $subgroup])
        </div>
    </div>
</div>
@endsection
