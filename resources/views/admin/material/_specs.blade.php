{{-- Specificatie-kaarten van een subgroep (gebruikt op subgroep- én machinepagina) --}}
<div class="card mb-3">
    <div class="card-header fw-bold">
        Specificaties
        @if($subgroup?->specifications)<span class="badge bg-success">{{ count($subgroup->specifications) }}</span>@endif
    </div>
    @if($subgroup?->specifications)
        <div class="card-body p-0" style="max-height:420px; overflow-y:auto;">
            <table class="table table-sm table-striped mb-0">
                <tbody>
                    @foreach($subgroup->specifications as $key => $value)
                        <tr><th class="ps-3" style="width:45%">{{ $key }}</th><td>{{ $value }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card-body text-muted">
            Geen specificaties — deze subgroep staat (nog) niet in de subgroeplijst.
        </div>
    @endif
</div>

@if($subgroup?->highlights)
    <div class="card mb-3">
        <div class="card-header fw-bold">Highlights</div>
        <ul class="list-group list-group-flush">
            @foreach($subgroup->highlights as $h)<li class="list-group-item">{{ $h }}</li>@endforeach
        </ul>
    </div>
@endif

@foreach(['accessoires' => 'Accessoires', 'verkoopartikelen' => 'Verkoopartikelen', 'alternatieven' => 'Alternatieven'] as $field => $label)
    @if($subgroup?->$field)
        <div class="card mb-3">
            <div class="card-header fw-bold">{{ $label }}</div>
            <ul class="list-group list-group-flush">
                @foreach($subgroup->$field as $item)<li class="list-group-item">{{ $item }}</li>@endforeach
            </ul>
        </div>
    @endif
@endforeach
