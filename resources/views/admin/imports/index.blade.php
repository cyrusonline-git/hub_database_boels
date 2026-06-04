@extends('layouts.app')
@section('title','Import Engine')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-upload text-boels"></i> Import Engine</h3>
    <a href="{{ route('admin.imports.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe import</a>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>#</th><th>Bestand</th><th>Status</th><th>Totaal</th><th>OK</th><th>Fout</th><th>Door</th><th>Datum</th><th></th></tr></thead>
        <tbody>
        @foreach($jobs as $j)
            <tr>
                <td>{{ $j->id }}</td>
                <td>{{ $j->original_filename }}</td>
                <td><span class="badge bg-secondary">{{ $j->status }}</span></td>
                <td>{{ $j->total_rows }}</td>
                <td class="text-success">{{ $j->imported_rows }}</td>
                <td class="text-danger">{{ $j->failed_rows }}</td>
                <td>{{ $j->user?->name }}</td>
                <td>{{ $j->created_at->format('d-m-Y H:i') }}</td>
                <td class="text-end"><a href="{{ route('admin.imports.show', $j) }}" class="btn btn-sm btn-outline-secondary">Details</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $jobs->links() }}</div>
@endsection
