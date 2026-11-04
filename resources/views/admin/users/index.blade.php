@extends('layouts.app')
@section('title','Gebruikers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3><i class="bi bi-people text-boels"></i> Gebruikers
        <small class="text-muted">({{ $users->total() }}@if($hasFilter ?? false) gefilterd @endif)</small>
    </h3>
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

{{-- Dynamisch filter: dropdowns filteren direct, het zoekveld filtert tijdens het typen --}}
<form method="GET" class="card p-3 mb-3" id="userFilter">
    <div class="row g-2">
        <div class="col-md-2">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" id="userFilterQ"
                   placeholder="Zoek op naam of e-mail" autocomplete="off">
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select auto-filter">
                <option value="">— Rol —</option>
                @foreach($filters['roles'] as $r)
                    <option value="{{ $r->id }}" @selected((string) request('role') === (string) $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <select name="bu" class="form-select auto-filter" title="Business unit (via de medewerkerkaart)">
                <option value="">— BU —</option>
                @foreach($filters['units'] as $u)<option value="{{ $u->id }}" @selected((string) request('bu') === (string) $u->id)>{{ $u->name }}</option>@endforeach
                <option value="geen" @selected(request('bu') === 'geen')>Zonder BU/kaart</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="depot" class="form-select auto-filter">
                <option value="">— Depot —</option>
                @foreach($filters['depots'] as $v)<option @selected(request('depot')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="area" class="form-select auto-filter">
                <option value="">— Area —</option>
                @foreach($filters['areas'] as $v)<option @selected(request('area')===$v)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select auto-filter">
                <option value="">— Status —</option>
                <option value="actief" @selected(request('status')==='actief')>Actief</option>
                <option value="wacht" @selected(request('status')==='wacht')>Wacht op activatie</option>
                <option value="inactief" @selected(request('status')==='inactief')>Inactief</option>
                <option value="nooit" @selected(request('status')==='nooit')>Nog nooit ingelogd</option>
                <option value="super" @selected(request('status')==='super')>Super-admins</option>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="Filters wissen"><i class="bi bi-x-circle"></i></a>
        </div>
    </div>
    <noscript><div class="mt-2"><button class="btn btn-boels btn-sm"><i class="bi bi-funnel"></i> Filter</button></div></noscript>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Naam</th><th>E-mail</th><th>Rollen</th><th>Depot / Area</th><th>Actief</th><th>Laatst ingelogd</th><th></th>
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
                    <td class="small">
                        @php $dep = collect($u->allowed_depots ?? []); $are = collect($u->allowed_areas ?? []); @endphp
                        @if($dep->isNotEmpty()){{ $dep->take(2)->implode(', ') }}@if($dep->count() > 2) <span class="text-muted">+{{ $dep->count() - 2 }}</span>@endif @endif
                        @if($are->isNotEmpty())<div class="text-muted">{{ $are->take(2)->implode(', ') }}@if($are->count() > 2) +{{ $are->count() - 2 }}@endif</div>@endif
                        @if($dep->isEmpty() && $are->isEmpty())<span class="text-muted">—</span>@endif
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
                @if($users->isEmpty())
                    <tr><td colspan="7" class="text-center text-muted py-4">Geen gebruikers gevonden voor dit filter.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->links() }}</div>

<script>
// Dynamisch filteren: dropdowns direct toepassen, zoekveld na een korte typ-pauze.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('userFilter');
    if (!form) return;
    form.querySelectorAll('.auto-filter').forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
    });
    var q = document.getElementById('userFilterQ'), timer = null, laatste = q ? q.value : '';
    if (q) {
        q.focus();
        try { q.setSelectionRange(q.value.length, q.value.length); } catch (e) {}
        q.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (q.value.trim() !== laatste.trim()) { laatste = q.value; form.submit(); }
            }, 450);
        });
        q.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); form.submit(); } });
    }
});
</script>
@endsection
