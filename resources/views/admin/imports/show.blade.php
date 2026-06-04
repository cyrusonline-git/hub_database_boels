@extends('layouts.app')
@section('title','Import #'.$job->id)

@section('content')
<h3 class="mb-3"><i class="bi bi-file-earmark-spreadsheet text-boels"></i> Import #{{ $job->id }}</h3>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Bestand:</strong> {{ $job->original_filename }}</p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-secondary">{{ $job->status }}</span></p>
                <p class="mb-1"><strong>Door:</strong> {{ $job->user?->name }}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Totaal:</strong> {{ $job->total_rows }}</p>
                <p class="mb-1 text-success"><strong>Geïmporteerd:</strong> {{ $job->imported_rows }}</p>
                <p class="mb-1 text-danger"><strong>Fouten:</strong> {{ $job->failed_rows }}</p>
            </div>
        </div>

        @if(in_array($job->status, ['ready', 'mapping']))
            <form action="{{ route('admin.imports.run', $job) }}" method="POST" class="mt-3">
                @csrf
                <button class="btn btn-boels"><i class="bi bi-play-fill"></i> Voer import uit</button>
                <a href="{{ route('admin.imports.mapping', $job) }}" class="btn btn-outline-secondary">Bewerk mapping</a>
            </form>
        @endif
    </div>
</div>

@if($job->rows->isNotEmpty())
<div class="card">
    <div class="card-header">Resultaten per rij ({{ $job->rows->count() }})</div>
    <table class="table mb-0 small">
        <thead class="table-light"><tr><th>#</th><th>Status</th><th>Fout</th><th>Data</th></tr></thead>
        <tbody>
        @foreach($job->rows as $r)
            <tr>
                <td>{{ $r->row_number }}</td>
                <td>
                    @if($r->status==='imported') <span class="badge bg-success">OK</span>
                    @elseif($r->status==='error') <span class="badge bg-danger">FOUT</span>
                    @else <span class="badge bg-secondary">{{ $r->status }}</span>
                    @endif
                </td>
                <td>{{ $r->error_message }}</td>
                <td><code>{{ json_encode($r->raw_data, JSON_UNESCAPED_UNICODE) }}</code></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
