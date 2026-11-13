{{-- Blok "In huur" op een machine-/artikelpagina; $inHuur uit KlantContext::voorMachine --}}
@if(!empty($inHuur['beschikbaar']))
<div class="card mb-3 {{ !empty($inHuur['regels']) ? 'border-warning' : '' }}">
    <div class="card-header fw-bold"><i class="bi bi-geo-alt text-boels me-1"></i>Waar staat dit nu? <small class="text-muted fw-normal">volgens SAP, stand {{ \Carbon\Carbon::parse($inHuur['stand'])->format('d-m H:i') }}</small></div>
    <div class="card-body">
        @forelse($inHuur['regels'] as $r)
            <div class="mb-2">
                <strong>{{ $r['customer_name'] }}</strong> · <a href="{{ $inHuur['dashboard'] }}/in-huur/{{ $r['contract_number'] }}">contract {{ $r['contract_number'] }}</a><br>
                <span class="small text-muted">{{ $r['project_description'] ?? $r['delivery_name'] }}{{ $r['delivery_address'] ? ' · '.$r['delivery_address'] : '' }} · sinds {{ $r['hire_date'] ? \Carbon\Carbon::parse($r['hire_date'])->format('d-m-Y') : '—' }} · {{ str_contains($r['status'], 'To Collect') ? 'afgemeld, ophalen '.($r['coll_date'] ? \Carbon\Carbon::parse($r['coll_date'])->format('d-m-Y') : '') : 'in huur' }} · € {{ number_format($r['weekly_hire_rate'], 2, ',', '.') }}/wk</span>
            </div>
        @empty
            <span class="text-muted">Niet in huur bij een klant van depot {{ config('sap.depot') ?: '—' }} (staat vermoedelijk op het depot, in onderhoud of bij een ander depot).</span>
        @endforelse
    </div>
</div>
@endif
