@extends('layouts.app')
@section('title', $group ? $group->group_name : 'Alle subgroepen')

@section('content')
<nav class="mb-2 small">
    <a href="{{ route('admin.material.index') }}">Materieel</a>
    @if($group)
        @if($group->analysis_group)
            &rsaquo; <a href="{{ route('admin.material.groups', ['analysis' => $group->analysis_group]) }}">{{ $group->analysis_group }}</a>
        @endif
        &rsaquo; <strong>{{ $group->group_name }}</strong>
    @else
        &rsaquo; <strong>Alle subgroepen</strong>
    @endif
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-boxes text-boels"></i>
        {{ $group ? $group->group_name : 'Alle subgroepen' }}
        <small class="text-muted">({{ $subgroups->total() }})</small>
    </h3>
    @if($group && $group->analysis_group)
        <a href="{{ route('admin.material.groups', ['analysis' => $group->analysis_group]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ $group->analysis_group }}
        </a>
    @endif
</div>

<form method="GET" class="card p-3 mb-3">
    @if($group)<input type="hidden" name="group" value="{{ $group->id }}">@endif
    <div class="row g-2">
        <div class="col-md-5">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Zoek subgroepnummer / naam / merk / type">
        </div>
        <div class="col-md-3">
            <select name="specs" class="form-select">
                <option value="">— Specs —</option>
                <option value="met" @selected(request('specs')==='met')>Met specificaties</option>
                <option value="zonder" @selected(request('specs')==='zonder')>Zonder specificaties</option>
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
                    <th>Subgroep</th>
                    <th>Naam</th>
                    <th>Merk / Type</th>
                    @unless($group)<th>Productgroep</th><th>Analysegroep</th>@endunless
                    <th class="text-end"># Machines</th>
                    <th class="text-center">Specs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subgroups as $sg)
                    <tr>
                        <td><strong>{{ $sg->subgroup_number }}</strong></td>
                        <td>{{ $sg->subgroup_name }}</td>
                        <td class="text-muted">{{ trim(($sg->merk ?? '').' '.($sg->type ?? '')) ?: '—' }}</td>
                        @unless($group)
                            <td>{{ $sg->group?->group_name }}</td>
                            <td class="text-muted">{{ $sg->group?->analysis_group ?? '—' }}</td>
                        @endunless
                        <td class="text-end">{{ number_format($sg->machines_count,0,',','.') }}</td>
                        <td class="text-center">
                            @if($sg->specifications)
                                <span class="badge bg-success">{{ count($sg->specifications) }}</span>
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.material.show', $sg) }}" class="btn btn-sm btn-outline-secondary">
                                Bekijk <i class="bi bi-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Geen subgroepen gevonden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $subgroups->links() }}</div>
@endsection
