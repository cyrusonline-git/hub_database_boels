@extends('layouts.app')
@section('title','Custom Fields')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-input-cursor-text text-boels"></i> Custom Fields</h3>
    <a href="{{ route('admin.custom-fields.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuw veld</a>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>Entiteit</th><th>Key</th><th>Label</th><th>Type</th><th>Verplicht</th><th></th></tr></thead>
        <tbody>
        @foreach($fields as $f)
            <tr>
                <td><code>{{ $f->entity }}</code></td>
                <td><code>{{ $f->key }}</code></td>
                <td>{{ $f->label }}</td>
                <td>{{ $f->type }}</td>
                <td>{!! $f->required ? '<i class="bi bi-check2 text-success"></i>' : '' !!}</td>
                <td class="text-end">
                    <a href="{{ route('admin.custom-fields.edit', $f) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('admin.custom-fields.destroy', $f) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $fields->links() }}</div>
@endsection
