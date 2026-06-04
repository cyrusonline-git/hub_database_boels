@extends('layouts.app')
@section('title','Permissies')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-key text-boels"></i> Permissies</h3>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe permissie</a>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>Applicatie</th><th>Key</th><th>Naam</th><th></th></tr></thead>
        <tbody>
        @foreach($permissions as $p)
            <tr>
                <td>{{ $p->application?->name ?? '— platform —' }}</td>
                <td><code>{{ $p->key }}</code></td>
                <td>{{ $p->name }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.permissions.edit', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('admin.permissions.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $permissions->links() }}</div>
@endsection
