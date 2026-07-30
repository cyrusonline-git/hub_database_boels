@extends('layouts.app')
@section('title', $analysis)

@section('content')
<nav class="mb-2 small">
    <a href="{{ route('admin.material.index') }}">Materieel</a>
    &rsaquo; <strong>{{ $analysis }}</strong>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-folder2-open text-boels"></i> {{ $analysis }}
        <small class="text-muted">({{ $groups->count() }} productgroepen)</small>
    </h3>
    <a href="{{ route('admin.material.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Alle analysegroepen
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Productgroep</th>
                    <th class="text-end"># Subgroepen</th>
                    <th class="text-end"># Unieke machines</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $g)
                    <tr>
                        <td><strong>{{ $g->group_name }}</strong></td>
                        <td class="text-end">{{ $g->subgroups_count }}</td>
                        <td class="text-end">{{ number_format($g->machines_count ?? 0,0,',','.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.material.subgroups', ['group' => $g->id]) }}" class="btn btn-sm btn-outline-secondary">
                                Subgroepen <i class="bi bi-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Geen productgroepen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
