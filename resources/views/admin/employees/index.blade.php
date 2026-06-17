@extends('layouts.app')
@section('title','Medewerkers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-person-badge text-boels"></i> Medewerkers
        <small class="text-muted">({{ $employees->total() }})</small>
    </h3>
    <a href="{{ url('/admin/imports/create') }}" class="btn btn-outline-secondary">
        <i class="bi bi-upload"></i> Importeer Excel
    </a>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Zoek op naam / email / nummer">
        </div>
        <div class="col-md-2">
            <select name="depot" class="form-select">
                <option value="">— Depot —</option>
                @foreach($filters['depots'] as $v)<option @selected(request('depot')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="area" class="form-select">
                <option value="">— Area —</option>
                @foreach($filters['areas'] as $v)<option @selected(request('area')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="country" class="form-select">
                <option value="">— Land —</option>
                @foreach($filters['countries'] as $v)<option @selected(request('country')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="function" class="form-select">
                <option value="">— Functie —</option>
                @foreach($filters['functions'] as $v)<option @selected(request('function')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1">
            <select name="status" class="form-select">
                <option value="">Alle</option>
                <option value="active" @selected(request('status')==='active')>Actief</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactief</option>
            </select>
        </div>
    </div>
    <div class="mt-2">
        <button class="btn btn-boels btn-sm"><i class="bi bi-funnel"></i> Filter</button>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>
</form>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Naam</th><th>Functie</th><th>Depot</th><th>Area</th><th>Land</th>
                <th>E-mail</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($employees as $e)
            <tr class="{{ $e->trashed() || ! $e->active ? 'text-muted' : '' }}">
                <td>
                    <strong>{{ $e->name }}</strong>
                    @if($e->manager)<br><small class="text-muted">manager: {{ $e->manager }}</small>@endif
                </td>
                <td>{{ $e->function }}</td>
                <td>{{ $e->depot }}</td>
                <td>{{ $e->area }}</td>
                <td>{{ $e->country }}</td>
                <td><small>{{ $e->email }}</small></td>
                <td>
                    @if($e->trashed())
                        <span class="badge bg-danger">verwijderd</span>
                    @elseif($e->active)
                        <span class="badge bg-success">actief</span>
                    @else
                        <span class="badge bg-warning text-dark">inactief</span>
                    @endif
                </td>
                <td class="text-end">
                    @if($e->trashed())
                        <form action="{{ route('admin.employees.restore', $e->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-success" title="Herstellen"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </form>
                    @else
                        <a href="{{ route('admin.employees.edit', $e) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('admin.employees.destroy', $e) }}" method="POST" class="d-inline" onsubmit="return confirm('Op inactief zetten?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $employees->links() }}</div>
@endsection
