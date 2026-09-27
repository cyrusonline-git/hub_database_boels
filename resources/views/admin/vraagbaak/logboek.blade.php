@extends('layouts.app')
@section('title', 'Vraagbaak — beheer')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0"><i class="bi bi-chat-square-text me-2 text-warning"></i>Boels Vraagbaak — beheer</h1>
        <div class="text-muted small">{{ $totaal }} vragen gesteld in totaal. Eigen AI: alle berekeningen op de eigen server.</div>
    </div>
    <a href="{{ route('tools.vraagbaak') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Naar de vraagbaak</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header fw-semibold">Bronnen (app-databases)</div>
            <div class="card-body p-0"><table class="table table-sm mb-0">
                <thead><tr><th>Bron</th><th>Gevonden</th><th>Leesbaar</th><th>Tabellen</th></tr></thead>
                <tbody>@foreach($bronnen as $b)
                    <tr><td>{{ config('vraagbaak.bronnen.'.$b['bron'].'.naam', $b['bron']) }}<br><small class="text-muted">{{ $b['pad'] }}</small></td>
                        <td>{!! $b['gevonden'] ? '<span class="badge bg-success">ja</span>' : '<span class="badge bg-danger">nee</span>' !!}</td>
                        <td>{!! $b['leesbaar'] ? '<span class="badge bg-success">ja</span>' : '<span class="badge bg-danger">nee</span>' !!}</td>
                        <td>{{ $b['tabellen'] ?? '-' }}@if($b['fout'])<br><small class="text-danger">{{ $b['fout'] }}</small>@endif</td></tr>
                @endforeach</tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100"><div class="card-header fw-semibold">Meest gesteld (90 dagen)</div>
            <ul class="list-group list-group-flush">@forelse($populair as $p)
                <li class="list-group-item d-flex justify-content-between"><span class="small">{{ \App\Vraagbaak\Catalogus::vind($p->herkend_als)?->titel ?? $p->herkend_als }}</span><span class="badge bg-light text-dark">{{ $p->n }}</span></li>
            @empty<li class="list-group-item text-muted small">Nog geen vragen.</li>@endforelse</ul>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card h-100"><div class="card-header fw-semibold">Niet begrepen (90 dagen)</div>
            <ul class="list-group list-group-flush">@forelse($onbegrepen as $o)
                <li class="list-group-item d-flex justify-content-between"><span class="small">{{ $o->vraag }}</span><span class="badge bg-light text-dark">{{ $o->n }}</span></li>
            @empty<li class="list-group-item text-muted small">Alles herkend.</li>@endforelse</ul>
            <div class="card-footer small text-muted">Voeg voor deze vragen synoniemen toe in <code>app/Vraagbaak/Bronnen/*.php</code>.</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Logboek</span>
        <form method="get" class="d-flex gap-2"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">alle statussen</option>
            @foreach(['ok' => 'beantwoord', 'keuze' => 'keuze gevraagd', 'onbekend' => 'niet herkend', 'fout' => 'fout'] as $k => $l)<option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>@endforeach
        </select></form></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>Wanneer</th><th>Wie</th><th>Vraag</th><th>Herkend als</th><th>Status</th><th>Parameters</th><th>ms</th><th>Feedback</th></tr></thead>
        <tbody>@foreach($log as $r)
            <tr><td class="text-nowrap">{{ $r->created_at->format('d-m H:i') }}</td><td>{{ $r->user?->name }}</td><td>{{ $r->vraag }}</td><td><small>{{ $r->herkend_als }}</small></td>
                <td><span class="badge {{ $r->status === 'ok' ? 'bg-success' : ($r->status === 'fout' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $r->status }}</span></td>
                <td><small>{{ collect($r->parameters ?? [])->filter()->map(fn ($v, $k) => "$k: $v")->implode(', ') }}</small></td><td>{{ $r->duur_ms }}</td><td><small>{{ $r->feedback }}</small></td></tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $log->links() }}</div>
</div>
@endsection
