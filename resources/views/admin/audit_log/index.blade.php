@extends('layouts.app')
@section('title','Audit Log')

@section('content')
<h3 class="mb-3"><i class="bi bi-clock-history text-boels"></i> Audit Log</h3>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input type="text" name="event" value="{{ request('event') }}" class="form-control" placeholder="Event (created/updated/deleted)"></div>
        <div class="col-md-4"><input type="text" name="type" value="{{ request('type') }}" class="form-control" placeholder="Entity class"></div>
        <div class="col-md-3"><input type="number" name="user_id" value="{{ request('user_id') }}" class="form-control" placeholder="User ID"></div>
        <div class="col-md-2"><button class="btn btn-boels w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <table class="table mb-0 align-middle small">
        <thead class="table-light"><tr><th>Tijd</th><th>Gebruiker</th><th>Event</th><th>Entiteit</th><th>ID</th><th>Wijzigingen</th></tr></thead>
        <tbody>
        @foreach($logs as $l)
            <tr>
                <td>{{ $l->created_at->format('d-m-Y H:i:s') }}</td>
                <td>{{ $l->user?->name ?? '— systeem —' }}</td>
                <td><span class="badge bg-secondary">{{ $l->event }}</span></td>
                <td><code>{{ class_basename($l->auditable_type) }}</code></td>
                <td>{{ $l->auditable_id }}</td>
                <td><pre class="mb-0" style="white-space:pre-wrap;font-size:11px;">{{ json_encode($l->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
