@extends('layouts.app')
@section('title','Field Aliases')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-arrow-left-right text-boels"></i> Field Aliases</h3>
    <a href="{{ route('admin.field-aliases.create') }}" class="btn btn-boels"><i class="bi bi-plus-lg"></i> Nieuwe alias</a>
</div>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>Entiteit</th><th>Alias (kolomkop)</th><th>Veld</th><th></th></tr></thead>
        <tbody>
        @foreach($aliases as $a)
            <tr>
                <td><code>{{ $a->entity }}</code></td>
                <td>{{ $a->alias }}</td>
                <td><code>{{ $a->field }}</code></td>
                <td class="text-end">
                    <a href="{{ route('admin.field-aliases.edit', $a) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('admin.field-aliases.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Verwijderen?');">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $aliases->links() }}</div>
@endsection
