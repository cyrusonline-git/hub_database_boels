@extends('layouts.app')
@section('title','Gebruikers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3><i class="bi bi-people text-boels"></i> Gebruikers</h3>
    <div class="d-flex gap-2">
        @if(($pendingCount ?? 0) > 0)
            <form action="{{ route('admin.users.mail-pending') }}" method="POST"
                  onsubmit="return confirm('Activatiemail sturen naar alle {{ $pendingCount }} gebruikers die nog wachten op activatie?\nLET OP: ook wie al eerder een mail kreeg (maar nog niet activeerde) krijgt er dan opnieuw één — de oude link vervalt.\nVoor één persoon: gebruik de envelop-knop bij die gebruiker.');">
                @csrf
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-envelope-paper"></i> Activatiemails versturen ({{ $pendingCount }} wachtend)
                </button>
            </form>
        @endif
        <a href="{{ route('admin.users.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe gebruiker</a>
    </div>
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
                    <td>
                        @if($u->status === \App\Models\User::STATUS_PENDING)
                            <span class="badge bg-warning text-dark">wacht op activatie</span><br>
                            <small class="text-muted">
                                {{ $u->activation_mail_sent_at ? 'gemaild ' . $u->activation_mail_sent_at->format('d-m H:i') : 'nog geen mail gehad' }}
                            </small>
                        @else
                            {!! $u->active ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}
                        @endif
                    </td>
                    <td>{{ $u->last_login_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    <td class="text-end">
                        <form action="{{ route('admin.users.send-login-mail', $u) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Inlog-mail sturen naar {{ $u->email }}?\nDaarmee kiest de medewerker zelf een (nieuw) wachtwoord.');">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" title="Stuur (opnieuw) een inlog-mail met activatielink">
                                <i class="bi bi-envelope-paper"></i>
                            </button>
                        </form>
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
