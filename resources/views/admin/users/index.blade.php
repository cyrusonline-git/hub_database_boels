@extends('layouts.app')
@section('title','Gebruikers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-people text-boels"></i> Gebruikers</h3>
    <a href="{{ route('admin.users.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe gebruiker</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Naam</th><th>E-mail</th><th>Rollen</th><th>Actief</th><th>Laatst ingelogd</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td>{{ $u->name }} @if($u->is_super_admin)<span class="badge bg-boels">SUPER</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @foreach($u->roles as $r)
                            <span class="badge bg-secondary">{{ $r->name }}</span>
                        @endforeach
                    </td>
                    <td>{!! $u->active ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td>
                    <td>{{ $u->last_login_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>
@endsection
