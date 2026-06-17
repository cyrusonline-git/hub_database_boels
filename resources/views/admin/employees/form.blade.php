@extends('layouts.app')
@section('title','Medewerker bewerken')

@section('content')
<h3 class="mb-3"><i class="bi bi-person-badge text-boels"></i> {{ $employee->name }}</h3>

<form action="{{ route('admin.employees.update', $employee) }}" method="POST" class="card p-4">
    @csrf @method('PUT')

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Personeelsnummer *</label>
            <input type="text" name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" class="form-control" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Naam *</label>
            <input type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Telefoon</label>
            <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Functie</label>
            <input type="text" name="function" value="{{ old('function', $employee->function) }}" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">Depot / Vestiging</label>
            <input type="text" name="depot" value="{{ old('depot', $employee->depot) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Area / Gebied</label>
            <input type="text" name="area" value="{{ old('area', $employee->area) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Land</label>
            <input type="text" name="country" value="{{ old('country', $employee->country) }}" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">Plaats</label>
            <input type="text" name="city" value="{{ old('city', $employee->city) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Regio</label>
            <input type="text" name="region" value="{{ old('region', $employee->region) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Afdeling</label>
            <select name="department_id" class="form-select">
                <option value="">— geen —</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id) == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Manager</label>
            <input type="text" name="manager" value="{{ old('manager', $employee->manager) }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kostenplaats</label>
            <input type="text" name="cost_center" value="{{ old('cost_center', $employee->cost_center) }}" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" id="active" class="form-check-input" @checked(old('active', $employee->active))>
                <label for="active" class="form-check-label">Actief</label>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-boels">Opslaan</button>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">Annuleren</a>
    </div>
</form>
@endsection
