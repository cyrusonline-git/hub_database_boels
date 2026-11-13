@extends('layouts.app')
@section('title','Uploads Smartview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-cloud-upload text-boels"></i> Uploads Smartview</h3>
    <a href="{{ route('admin.sap-uploads.codes') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person-badge"></i> Smartview-codes → medewerkers
        @if($ongekoppeld)<span class="badge bg-warning text-dark ms-1">{{ $ongekoppeld }} open</span>@endif
    </a>
</div>

@if (session('import_error'))<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('import_error') }}</div>@endif
@if (session('import_result'))<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('import_result') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<p class="text-muted">
    Vier Smartview-rapporten voeden het Industrial-dashboard en de andere apps. Exporteer ze in Smartview met de
    standaardnaam hieronder en sleep ze op de juiste kaart. CORE herkent het bestand aan de kopregel;
    een verkeerd bestand wordt geweigerd. Alleen contracten van depot <strong>{{ config('sap.depot') ?: 'alle' }}</strong> worden meegenomen.
</p>

<div class="row g-3 mb-4">
@foreach($soorten as $sleutel => $s)
    <div class="col-lg-6">
        <div class="card h-100 {{ $s['verouderd'] ? 'border-warning' : '' }}">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-1">{{ $s['label'] }}</h5>
                    <span class="badge {{ $s['frequentie_dagen'] <= 1 ? 'bg-boels' : 'bg-secondary' }}">{{ $s['frequentie_dagen'] <= 1 ? 'dagelijks' : 'wekelijks' }}</span>
                </div>
                <div class="small mb-2"><i class="bi bi-file-earmark-excel text-success"></i> <code>{{ $s['bestand'] }}</code></div>
                <p class="text-muted small">{{ $s['omschrijving'] }}</p>
                @if($s['laatste'])
                    <div class="small mb-3 {{ $s['verouderd'] ? 'text-warning' : 'text-muted' }}">
                        <i class="bi {{ $s['verouderd'] ? 'bi-exclamation-triangle' : 'bi-clock-history' }}"></i>
                        Laatste stand: <strong>{{ $s['laatste']->created_at->format('d-m-Y H:i') }}</strong>
                        door {{ $s['laatste']->user?->name ?? 'script' }} ·
                        {{ number_format($s['laatste']->regels, 0, ',', '.') }} regels, {{ $s['laatste']->contracten }} contracten/orders
                        @if($s['verouderd']) · <strong>verouderd</strong>@endif
                        @if(is_array($s['laatste']->samenvatting))
                            <br>
                            @foreach(array_filter($s['laatste']->samenvatting, fn($v) => !is_array($v)) as $k => $v)
                                <span class="me-2">{{ str_replace('_', ' ', $k) }}: <strong>{{ is_numeric($v) && str_contains((string) $v, '.') ? '€ '.number_format($v, 2, ',', '.') : $v }}</strong></span>
                            @endforeach
                        @endif
                    </div>
                @else
                    <div class="small text-warning mb-3"><i class="bi bi-exclamation-triangle"></i> Nog nooit geüpload.</div>
                @endif
                <form method="POST" action="{{ route('admin.sap-uploads.upload') }}" enctype="multipart/form-data" class="d-flex gap-2 align-items-center sap-drop" data-soort="{{ $sleutel }}">
                    @csrf
                    <input type="hidden" name="soort" value="{{ $sleutel }}">
                    <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                    <button type="submit" class="btn btn-boels btn-sm text-nowrap"><i class="bi bi-upload"></i> Verwerken</button>
                </form>
            </div>
        </div>
    </div>
@endforeach
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-1"></i> Laatste uploads</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>#</th><th>Wanneer</th><th>Soort</th><th>Bestand</th><th>Door</th><th class="text-end">Regels</th><th class="text-end">Contracten</th><th class="text-end">Overgeslagen</th><th>Rapportdatum</th></tr></thead>
            <tbody>
            @forelse($historie as $u)
                <tr>
                    <td class="text-muted">{{ $u->id }}</td>
                    <td>{{ $u->created_at->format('d-m-Y H:i') }}</td>
                    <td>{{ config('sap.soorten.'.$u->soort.'.label', $u->soort) }}</td>
                    <td class="small">{{ $u->bestandsnaam }}</td>
                    <td>{{ $u->user?->name ?? 'script' }}</td>
                    <td class="text-end">{{ number_format($u->regels, 0, ',', '.') }}</td>
                    <td class="text-end">{{ $u->contracten }}</td>
                    <td class="text-end">{{ $u->overgeslagen }}</td>
                    <td>{{ $u->rapportdatum?->format('d-m-Y') }}{{ $u->regels_opgeruimd ? ' · regels opgeruimd' : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted text-center py-3">Nog geen uploads.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    Ook via script: <code>curl -F file=@"&lt;bestand&gt;" "{{ url('/__sap_upload.php') }}?k=&lt;DEPLOY_SECRET&gt;"</code>
    (soort wordt herkend) — of op de server <code>php artisan sap:import &lt;pad&gt;</code>.
</p>
@endsection

@push('scripts')
<script>
// Slepen op een kaart = bestand kiezen + direct verwerken
document.querySelectorAll('.sap-drop').forEach(form => {
    const card = form.closest('.card');
    ['dragenter','dragover'].forEach(ev => card.addEventListener(ev, e => { e.preventDefault(); card.classList.add('border-boels','shadow'); }));
    ['dragleave','drop'].forEach(ev => card.addEventListener(ev, e => { e.preventDefault(); card.classList.remove('border-boels','shadow'); }));
    card.addEventListener('drop', e => {
        const input = form.querySelector('input[type=file]');
        if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; form.requestSubmit(); }
    });
});
</script>
@endpush
