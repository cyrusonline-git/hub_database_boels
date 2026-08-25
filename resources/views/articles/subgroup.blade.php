@extends('layouts.app')
@section('title',$subgroup->subgroup_number.' — '.$subgroup->subgroup_name)

@section('content')
<div class="mx-auto" style="max-width: 1100px;">
    <nav class="mb-2 small">
        <a href="{{ route('articles.index') }}">Artikelen</a>
        &rsaquo; <strong>{{ $subgroup->subgroup_number }} — {{ $subgroup->subgroup_name }}</strong>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">
            <i class="bi bi-boxes text-boels"></i>
            {{ $subgroup->subgroup_name }}
            <small class="text-muted">{{ $subgroup->subgroup_number }}</small>
        </h3>
        <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-search"></i> Nieuw zoeken
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            @include('admin.material._specs', ['subgroup' => $subgroup])
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold">
                    Artikelnummers <span class="badge bg-secondary">{{ $machines->total() }}</span>
                </div>
                <div class="list-group list-group-flush" style="max-height:480px; overflow-y:auto;">
                    @forelse($machines as $m)
                        <a href="{{ route('articles.show', $m) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-gear-wide-connected text-boels"></i>
                            <span class="fw-bold">{{ $m->machine_number }}</span>
                            <span class="flex-grow-1 text-muted small text-truncate">{{ $m->description }}</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">Geen losse artikelnummers bekend in deze groep.</div>
                    @endforelse
                </div>
                @if($machines->hasPages())
                    <div class="card-body py-2">{{ $machines->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
