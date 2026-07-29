@extends('layouts.app')
@section('title','Materieel import')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-truck text-boels"></i> Materieel import</h3>
</div>

@if (session('import_error'))
    <div class="alert alert-danger">{{ session('import_error') }}</div>
@endif

@if (session('import_result'))
    @php($r = session('import_result'))
    <div class="alert alert-success">
        <strong>{{ $r['label'] }} verwerkt:</strong>
        {{ $r['created'] }} nieuw, {{ $r['updated'] }} bijgewerkt, {{ $r['skipped'] }} overgeslagen (lege rijen).
        @if (!empty($r['unknown_subgroups']))
            <hr class="my-2">
            <i class="bi bi-exclamation-triangle"></i>
            {{ count($r['unknown_subgroups']) }} subgroep(en) stonden nog niet in de subgroeplijst en zijn
            zonder specificaties aangemaakt: {{ implode(', ', array_slice($r['unknown_subgroups'], 0, 20)) }}@if(count($r['unknown_subgroups']) > 20)…@endif
            <br>Upload (opnieuw) de subgroeplijst om specificaties aan te vullen.
        @endif
        @if (!empty($r['errors']))
            <hr class="my-2">
            <strong>{{ count($r['errors']) }} rij(en) met fouten:</strong>
            <ul class="mb-0 small">
                @foreach (array_slice($r['errors'], 0, 15) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if (count($r['errors']) > 15)<li>… en {{ count($r['errors']) - 15 }} meer</li>@endif
            </ul>
        @endif
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold">{{ $counts['groups'] }}</div>
            <div class="text-muted">Productgroepen</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold">{{ $counts['subgroups'] }}</div>
            <div class="text-muted">Subgroepen</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div class="fs-3 fw-bold">{{ $counts['machines'] }}</div>
            <div class="text-muted">Unieke materieelnummers</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h5 class="card-title"><i class="bi bi-1-circle text-boels"></i> Subgroeplijst met specificaties</h5>
                <p class="text-muted small">
                    Eén rij per <strong>subgroep</strong> (kolom D), met productgroep, omschrijving en
                    alle specificatie-kolommen. De hiërarchie
                    <em>Productgroep &rsaquo; Subgroep</em> wordt automatisch opgebouwd;
                    specificaties, highlights, accessoires, verkoopartikelen en alternatieven
                    worden per subgroep opgeslagen. Bestaande subgroepen worden bijgewerkt.
                </p>
                <form method="POST" action="{{ route('admin.material-imports.subgroups') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-boels">
                        <i class="bi bi-upload"></i> Upload subgroeplijst
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h5 class="card-title"><i class="bi bi-2-circle text-boels"></i> Unieke materieellijst</h5>
                <p class="text-muted small">
                    Eén rij per <strong>uniek materieelnummer</strong>. Verwachte kolommen:
                    <em>Analysis group, Product group, Subgroep, Unique number, Omschrijving</em>
                    (herkend op naam, volgorde maakt niet uit). Bouwt de volledige hiërarchie
                    <em>Analysegroep &rsaquo; Productgroep &rsaquo; Subgroep &rsaquo; Uniek nummer</em> op.
                    Bestaande nummers worden bijgewerkt; geschikt voor grote lijsten (65k+ rijen).
                </p>
                <form method="POST" action="{{ route('admin.material-imports.machines') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <button type="submit" class="btn btn-boels">
                        <i class="bi bi-upload"></i> Upload unieke lijst
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
