@extends('layouts.app')
@section('title','Klanten')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-building text-boels"></i> Klanten
        <small class="text-muted">({{ $customers->total() }})</small>
    </h3>
    <button class="btn btn-boels" data-bs-toggle="collapse" data-bs-target="#uploadCard">
        <i class="bi bi-upload"></i> Upload klantenlijst
    </button>
</div>

@if (session('import_error'))
    <div class="alert alert-danger">{{ session('import_error') }}</div>
@endif

@if (session('import_result'))
    @php($r = session('import_result'))
    <div class="alert alert-success">
        <strong>Klantenlijst verwerkt:</strong>
        {{ $r['created'] }} nieuw, {{ $r['updated'] }} bijgewerkt, {{ $r['skipped'] }} overgeslagen (lege rijen).
        @if (!empty($r['errors']))
            <hr class="my-2">
            <strong>{{ count($r['errors']) }} rij(en) met fouten:</strong>
            <ul class="mb-0 small">
                @foreach (array_slice($r['errors'], 0, 15) as $err)<li>{{ $err }}</li>@endforeach
                @if (count($r['errors']) > 15)<li>… en {{ count($r['errors']) - 15 }} meer</li>@endif
            </ul>
        @endif
    </div>
@endif

<div class="collapse {{ session('import_error') ? 'show' : '' }} mb-3" id="uploadCard">
    <div class="card">
        <div class="card-body p-4">
            <h5 class="card-title">Klantenlijst Industrial uploaden</h5>
            <p class="text-muted small">
                Verwachte kolommen: <em>Debtor, Debtor name, Debtor second name, Debtor responsible (+ rol),
                Concern (+ naam, verantwoordelijke, rol), Purchasing organisation (+ naam, verantwoordelijke, rol),
                NACE CODE en omschrijving</em>. Herkenning op kolomnaam; "-" telt als leeg.
                Bestaande klanten worden bijgewerkt op debiteurnummer.
            </p>
            <form method="POST" action="{{ route('admin.customers.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-boels"><i class="bi bi-upload"></i> Uploaden</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Zoek op nummer / naam / concern / verantwoordelijke">
        </div>
        <div class="col-md-3">
            <select name="concern" class="form-select">
                <option value="">— Concern —</option>
                @foreach($concerns as $c)
                    <option value="{{ $c->concern_number }}" @selected(request('concern')===$c->concern_number)>
                        {{ $c->concern_name ?? $c->concern_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="nace" class="form-select">
                <option value="">— Branche (NACE) —</option>
                @foreach($naceCodes as $n)
                    <option value="{{ $n->nace_code }}" @selected(request('nace')===$n->nace_code)>
                        {{ $n->nace_code }} — {{ Str::limit($n->nace_description, 40) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-boels w-100"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Debiteur</th>
                    <th>Naam</th>
                    <th>Concern</th>
                    <th>Verantwoordelijke</th>
                    <th>Branche (NACE)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    <tr>
                        <td><strong>{{ $c->customer_number }}</strong></td>
                        <td>
                            {{ $c->customer_name }}
                            @if($c->second_name)<div class="small text-muted">{{ $c->second_name }}</div>@endif
                        </td>
                        <td>{{ $c->concern_name ?? '—' }}</td>
                        <td>
                            {{ $c->responsible ?? '—' }}
                            @if($c->responsible_role)<div class="small text-muted">{{ $c->responsible_role }}</div>@endif
                        </td>
                        <td class="small text-muted">{{ $c->nace_description ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.customers.show', $c) }}" class="btn btn-sm btn-outline-secondary">
                                Bekijk <i class="bi bi-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Geen klanten gevonden. Upload de klantenlijst hierboven.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $customers->links() }}</div>
@endsection
