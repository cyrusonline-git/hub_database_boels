@extends('layouts.app')
@section('title','Applicaties')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-grid text-boels"></i> Applicaties</h3>
    <a href="{{ route('admin.applications.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe applicatie</a>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light">
            <tr><th>#</th><th>Icoon</th><th>Naam</th><th>Slug</th><th>URL</th><th>Actief</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($applications as $a)
            <tr>
                <td>{{ $a->sort_order }}</td>
                <td><i class="{{ $a->icon ?: 'bi-app' }}" style="color: {{ $a->color }}; font-size:22px;"></i></td>
                <td><strong>{{ $a->name }}</strong><br><small class="text-muted">{{ $a->description }}</small></td>
                <td><code>{{ $a->slug }}</code></td>
                <td><a href="{{ $a->url }}" target="_blank">{{ $a->url }}</a></td>
                <td>{!! $a->active ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td>
                <td class="text-end">
                    <a href="{{ route('admin.applications.edit',$a) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('admin.applications.destroy',$a) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $applications->links() }}</div>
@endsection
