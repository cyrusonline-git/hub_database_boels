{{-- Blok "Bij deze klant (Smartview)" op de klantkaart; $sap uit KlantContext::voorKlant --}}
@if(!empty($sap['beschikbaar']))
@php($d = $sap['dashboard'])
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam text-boels me-1"></i>Bij deze klant volgens Smartview</span>
        @if($sap['stand'])<small class="text-muted fw-normal">stand {{ \Carbon\Carbon::parse($sap['stand'])->format('d-m H:i') }}</small>@endif
    </div>
    <div class="card-body">
        <div class="row g-2 text-center mb-3">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="fs-5 fw-bold">{{ $sap['huur']['contracten'] ?? 0 }}</div><div class="small text-muted">contracten in huur</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="fs-5 fw-bold">€ {{ number_format($sap['huur']['weekhuur'] ?? 0, 0, ',', '.') }}</div><div class="small text-muted">weekhuur</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2 {{ ($sap['huur']['to_collect_contracten'] ?? 0) ? 'border-danger' : '' }}"><div class="fs-5 fw-bold">{{ $sap['huur']['to_collect_contracten'] ?? 0 }}</div><div class="small text-muted">afgemeld, op te halen</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2 {{ ($sap['factuurrun']['zonder_reden'] ?? 0) ? 'border-warning' : '' }}"><div class="fs-5 fw-bold">{{ $sap['factuurrun']['contracten'] ?? 0 }}</div><div class="small text-muted">uit factuurrun{{ ($sap['factuurrun']['zonder_reden'] ?? 0) ? ' · '.$sap['factuurrun']['zonder_reden'].' zonder reden' : '' }}</div></div></div>
        </div>
        @if(!empty($sap['huur']['top']))
        <div class="small text-uppercase text-muted fw-semibold mb-1">Grootste contracten</div>
        <table class="table table-sm mb-2"><tbody>
            @foreach($sap['huur']['top'] as $c)
            <tr><td><a href="{{ $d }}/in-huur/{{ $c['contract_number'] }}"><code>{{ $c['contract_number'] }}</code></a></td><td class="small">{{ $c['project'] ?? $c['delivery_name'] }}</td><td class="small text-muted">{{ $c['regels'] }} regels · sinds {{ $c['sinds'] ? \Carbon\Carbon::parse($c['sinds'])->format('d-m-Y') : '—' }}</td><td class="text-end">€ {{ number_format($c['weekhuur'], 0, ',', '.') }}/wk</td><td>@if($c['to_collect'] > 0)<span class="badge bg-danger">{{ $c['to_collect'] }} afgemeld</span>@endif</td></tr>
            @endforeach
        </tbody></table>
        @endif
        @if(!empty($sap['planning']))
        <div class="small text-uppercase text-muted fw-semibold mb-1">Aankomende orders</div>
        <ul class="list-unstyled small mb-2">
            @foreach($sap['planning'] as $o)<li><a href="{{ $d }}/planning/{{ $o['order_number'] }}"><code>{{ $o['order_number'] }}</code></a> {{ ucfirst(str_replace('_', ' ', $o['soort'])) }} · {{ \Carbon\Carbon::parse($o['delivery_date'])->format('d-m-Y') }} · {{ $o['picked'] + 0 }}/{{ $o['total'] + 0 }} gepickt</li>@endforeach
        </ul>
        @endif
        @if(!empty($sap['offertes']))
        <div class="small text-uppercase text-muted fw-semibold mb-1">Offertes en toekomstige contracten (2.6)</div>
        <ul class="list-unstyled small mb-2">
            @foreach($sap['offertes'] as $o)<li><a href="{{ $d }}/planning/{{ $o['contract_number'] }}"><code>{{ $o['contract_number'] }}</code></a> {{ $o['is_contract'] ? 'contract' : 'offerte' }} ({{ $o['offerte_status'] }}) · lever {{ $o['delivery_date'] ? \Carbon\Carbon::parse($o['delivery_date'])->format('d-m-Y') : '—' }} · € {{ number_format($o['goods_value'], 0, ',', '.') }} · {{ $o['regels'] }} regels</li>@endforeach
        </ul>
        @endif
        <div class="small">
            <a href="{{ $d }}/in-huur?customer={{ urlencode($customer->customer_number) }}">Alles in huur</a>
            @if(($sap['factuurrun']['contracten'] ?? 0) > 0) · <a href="{{ $d }}/factuurrun?mijn=0&customer={{ urlencode($customer->customer_number) }}">Uit factuurrun</a>@endif
            · <a href="{{ $d }}/planning?q={{ urlencode($customer->customer_name) }}&alles=1">Planning</a>
        </div>
    </div>
</div>
@endif
