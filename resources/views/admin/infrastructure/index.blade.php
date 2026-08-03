@extends('layouts.app')
@section('title','Infrastructuur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-diagram-3 text-boels"></i> Infrastructuur</h3>
    <form method="POST" action="{{ route('admin.infrastructure.sync') }}">
        @csrf
        <button class="btn btn-boels" title="Leidt area's en depots af uit de velden op de medewerkers">
            <i class="bi bi-magic"></i> Vul automatisch uit medewerkerslijst
        </button>
    </form>
</div>

<p class="text-muted small">
    De organisatiestructuur van hoog naar laag: <strong>business unit → area → depot</strong>.
    Apps kunnen deze structuur ophalen via <code>/api/infrastructure</code>, zodat overal
    dezelfde indeling geldt. Medewerker-aantallen worden gematcht op de area/depot-velden
    van de medewerkers.
</p>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@forelse($units as $unit)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-building text-boels"></i> {{ $unit->name }}
                <small class="text-muted">({{ $unit->areas->count() }} area's,
                {{ $unit->areas->sum(fn($a) => $a->depots->count()) }} depots)</small>
            </h5>
            <form action="{{ route('admin.infrastructure.units.destroy', $unit) }}" method="POST"
                  onsubmit="return confirm('{{ $unit->name }} en alle onderliggende area\'s/depots verwijderen?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($unit->areas as $area)
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <form action="{{ route('admin.infrastructure.areas.update', $area) }}" method="POST"
                                  class="d-flex align-items-center gap-1 mb-2">
                                @csrf @method('PUT')
                                <i class="bi bi-geo-alt text-boels"></i>
                                <input type="text" name="name" value="{{ $area->name }}" class="form-control form-control-sm fw-bold" style="max-width:200px;"
                                       title="Naam aanpassen werkt automatisch door bij medewerkers, gebruikers en apps">
                                <input type="text" name="country" value="{{ $area->country }}" class="form-control form-control-sm" style="max-width:70px;" placeholder="Land">
                                <button class="btn btn-sm btn-outline-secondary py-0" title="Opslaan (naamswijziging werkt overal door)"><i class="bi bi-check-lg"></i></button>
                                <small class="text-muted text-nowrap ms-1">{{ $employeesPerArea[$area->name] ?? 0 }} mdw.</small>
                                <span class="ms-auto"></span>
                            </form>
                            <form action="{{ route('admin.infrastructure.areas.destroy', $area) }}" method="POST" class="float-end"
                                  onsubmit="return confirm('Area {{ $area->name }} incl. depots verwijderen?');" style="margin-top:-34px;">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                            </form>

                            <table class="table table-sm mb-2">
                                <tbody>
                                    @forelse($area->depots as $depot)
                                        <tr>
                                            <td colspan="2">
                                                <form action="{{ route('admin.infrastructure.depots.update', $depot) }}" method="POST" class="d-flex gap-1">
                                                    @csrf @method('PUT')
                                                    <input type="text" name="name" value="{{ $depot->name }}" class="form-control form-control-sm"
                                                           title="Naam aanpassen werkt automatisch door bij medewerkers, gebruikers en apps">
                                                    <input type="email" name="email" value="{{ $depot->email }}" class="form-control form-control-sm" placeholder="E-mail (optioneel)">
                                                    <button class="btn btn-sm btn-outline-secondary py-0" title="Opslaan (naamswijziging werkt overal door)"><i class="bi bi-check-lg"></i></button>
                                                </form>
                                            </td>
                                            <td class="text-end text-muted small text-nowrap">{{ $employeesPerDepot[$depot->name] ?? 0 }} mdw.</td>
                                            <td class="text-end" style="width:1%;">
                                                <form action="{{ route('admin.infrastructure.depots.destroy', $depot) }}" method="POST"
                                                      onsubmit="return confirm('Depot {{ $depot->name }} verwijderen?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="bi bi-x"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted small">Nog geen depots.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <form method="POST" action="{{ route('admin.infrastructure.depots.store') }}" class="row g-1">
                                @csrf
                                <input type="hidden" name="area_id" value="{{ $area->id }}">
                                <div class="col-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="Nieuw depot" required></div>
                                <div class="col-5"><input type="email" name="email" class="form-control form-control-sm" placeholder="E-mail (optioneel)"></div>
                                <div class="col-2"><button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-plus"></i></button></div>
                            </form>
                        </div>
                    </div>
                @endforeach

                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <strong class="text-muted"><i class="bi bi-plus-circle"></i> Nieuwe area</strong>
                        <form method="POST" action="{{ route('admin.infrastructure.areas.store') }}" class="row g-1 mt-1">
                            @csrf
                            <input type="hidden" name="business_unit_id" value="{{ $unit->id }}">
                            <div class="col-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="bv. Oost" required></div>
                            <div class="col-4"><input type="text" name="country" class="form-control form-control-sm" placeholder="Land (bv. NL)"></div>
                            <div class="col-3"><button class="btn btn-sm btn-boels w-100">Toevoegen</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">
        Nog geen structuur. Klik op <strong>"Vul automatisch uit medewerkerslijst"</strong> om
        Industrial met alle area's en depots uit je medewerkersdata op te bouwen — of voeg
        hieronder handmatig een business unit toe.
    </div>
@endforelse

<div class="card p-3">
    <form method="POST" action="{{ route('admin.infrastructure.units.store') }}" class="row g-2 align-items-center">
        @csrf
        <div class="col-auto fw-bold">Nieuwe business unit:</div>
        <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="bv. Rental" required></div>
        <div class="col-auto"><button class="btn btn-boels"><i class="bi bi-plus-lg"></i> Toevoegen</button></div>
    </form>
</div>
@endsection
