@extends('layouts.app')
@section('title', $customer->customer_name)

@section('content')
<nav class="mb-2 small">
    <a href="{{ route('admin.customers.index') }}">Klanten</a>
    @if($customer->concern_name)
        &rsaquo; <a href="{{ route('admin.customers.index', ['concern' => $customer->concern_number]) }}">{{ $customer->concern_name }}</a>
    @endif
    &rsaquo; <strong>{{ $customer->customer_number }}</strong>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-building text-boels"></i>
        {{ $customer->customer_name }}
        <small class="text-muted">#{{ $customer->customer_number }}</small>
    </h3>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Terug naar klanten
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-bold">Debiteur</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Debiteurnummer</th><td>{{ $customer->customer_number }}</td></tr>
                        <tr><th class="ps-3">Naam</th><td>{{ $customer->customer_name }}</td></tr>
                        <tr><th class="ps-3">Tweede naam</th><td>{{ $customer->second_name ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td>{{ $customer->responsible ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Rol</th><td>{{ $customer->responsible_role ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Branche (NACE)</th>
                            <td>@if($customer->nace_code){{ $customer->nace_code }} — {{ $customer->nace_description }}@else — @endif</td></tr>
                        <tr><th class="ps-3">Bron</th><td>{{ $customer->source_system ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-bold">Inkooporganisatie</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Nummer</th><td>{{ $customer->purchasing_org_number ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Naam</th><td>{{ $customer->purchasing_org_name ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td>{{ $customer->purchasing_org_responsible ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Rol</th><td>{{ $customer->purchasing_org_responsible_role ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-bold">Concern</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Concernnummer</th><td>{{ $customer->concern_number ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Naam</th><td>{{ $customer->concern_name ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td>{{ $customer->concern_responsible ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Rol</th><td>{{ $customer->concern_responsible_role ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-bold">
                Andere debiteuren binnen dit concern
                <span class="badge bg-secondary">{{ $concernCustomers->count() }}</span>
            </div>
            @if($concernCustomers->isEmpty())
                <div class="card-body text-muted">Geen andere debiteuren in dit concern.</div>
            @else
                <ul class="list-group list-group-flush" style="max-height:340px; overflow-y:auto;">
                    @foreach($concernCustomers as $cc)
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="{{ route('admin.customers.show', $cc) }}">{{ $cc->customer_name }}</a>
                            <span class="text-muted">#{{ $cc->customer_number }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
