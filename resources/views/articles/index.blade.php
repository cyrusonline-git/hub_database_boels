@extends('layouts.app')
@section('title','Artikelen zoeken')

@section('content')
<div class="mx-auto" style="max-width: 960px;">
    <div class="text-center mb-4 mt-2">
        <h3 class="mb-1"><i class="bi bi-box-seam text-boels"></i> Artikelen zoeken</h3>
        <p class="text-muted mb-0">Zoek op naam of artikelnummer — specificaties komen uit de geüploade materieellijst.</p>
    </div>

    <form method="GET" action="{{ route('articles.index') }}" class="input-group input-group-lg mb-4 shadow-sm" style="border-radius:14px; overflow:hidden;">
        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
        <input type="search" name="q" value="{{ $q }}" class="form-control border-0"
               placeholder="Bijv. generator, heftruck of 700123…" autofocus>
        <button class="btn btn-boels px-4">Zoeken</button>
    </form>

    @if($q === '')
        <div class="text-center text-muted py-5">
            <i class="bi bi-box-seam" style="font-size:48px; opacity:.3;"></i>
            <p class="mt-2">Typ hierboven een naam of artikelnummer om te zoeken.</p>
        </div>
    @else
        @if($subgroups->isNotEmpty())
            <h6 class="text-uppercase text-muted small fw-bold mb-2">Producttypes ({{ $subgroups->count() }})</h6>
            <div class="row g-3 mb-4">
                @foreach($subgroups as $sg)
                    <div class="col-md-6">
                        <a href="{{ route('articles.subgroup', $sg) }}" class="text-decoration-none">
                            <div class="card app-tile h-100 p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-boels" style="font-size:26px;"><i class="bi bi-boxes"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">{{ $sg->subgroup_number }} — {{ $sg->subgroup_name }}</div>
                                        <small class="text-muted">
                                            {{ trim(($sg->merk ?? '').' '.($sg->type ?? '')) ?: ($sg->group?->group_name ?? '') }}
                                            · {{ $sg->machines_count }} machine{{ $sg->machines_count === 1 ? '' : 's' }}
                                            @if($sg->specifications) · <span class="text-success">{{ count($sg->specifications) }} specs</span>@endif
                                        </small>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <h6 class="text-uppercase text-muted small fw-bold mb-2">Artikelnummers</h6>
        @if($machines->isEmpty() && $subgroups->isEmpty())
            <div class="alert alert-warning">Niets gevonden voor "<strong>{{ $q }}</strong>". Probeer een deel van de naam of het nummer.</div>
        @elseif($machines->isEmpty())
            <p class="text-muted small">Geen losse artikelnummers gevonden voor "<strong>{{ $q }}</strong>".</p>
        @else
            <div class="card shadow-sm mb-3">
                <div class="list-group list-group-flush">
                    @foreach($machines as $m)
                        <a href="{{ route('articles.show', $m) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2">
                            <i class="bi bi-gear-wide-connected text-boels"></i>
                            <span class="fw-bold">{{ $m->machine_number }}</span>
                            <span class="flex-grow-1 text-muted">{{ $m->description }}</span>
                            @if($m->subgroup?->specifications)<span class="badge bg-success">specs</span>@endif
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            {{ $machines->links() }}
        @endif
    @endif
</div>
@endsection
