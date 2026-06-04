@extends('layouts.app')
@section('title','Tabel-eigendom')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-diagram-3 text-boels"></i> Tabel-eigendom (CORE vs child-apps)</h3>
</div>

<div class="alert alert-info">
    Deze tabel toont per database-tabel <strong>welke app eigenaar is</strong>.
    CORE-tabellen (locked = ja) mogen alleen door Boels CORE worden gewijzigd.
    Child-apps krijgen via MySQL <strong>technisch</strong> geen ALTER-rechten op deze tabellen.
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light">
            <tr><th>Tabel</th><th>Eigenaar</th><th>Locked</th><th>Notitie</th></tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td><code>{{ $r['table_name'] }}</code></td>
                <td>
                    @if($r['owner_slug'] === 'core')
                        <span class="badge bg-boels">CORE</span>
                    @elseif($r['owner_slug'] === '— onbekend —')
                        <span class="badge bg-warning text-dark">onbekend</span>
                    @else
                        <span class="badge bg-secondary">{{ $r['owner_slug'] }}</span>
                    @endif
                    {{ $r['owner_name'] }}
                </td>
                <td>{!! $r['locked'] ? '<i class="bi bi-lock-fill text-danger"></i> locked' : '<i class="bi bi-unlock text-success"></i>' !!}</td>
                <td class="small text-muted">{{ $r['notes'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
