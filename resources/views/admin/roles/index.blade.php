@extends('layouts.app')
@section('title','Rollen')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-shield-lock text-boels"></i> Rollen</h3>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe rol</a>
</div>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr><th>Naam</th><th>Slug</th><th>Permissies</th><th>Gebruikers</th><th>Systeem</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($roles as $r)
            <tr>
                <td><strong>{{ $r->name }}</strong><br><small class="text-muted">{{ $r->description }}</small></td>
                <td><code>{{ $r->slug }}</code></td>
                <td>{{ $r->permissions_count }}</td>
                <td>{{ $r->users_count }}</td>
                <td>{!! $r->is_system ? '<span class="badge bg-secondary">systeem</span>' : '' !!}</td>
                <td class="text-end">
                    <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    @unless($r->is_system)
                    <form action="{{ route('admin.roles.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
