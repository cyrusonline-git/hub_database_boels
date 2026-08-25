@extends('layouts.app')
@section('title','Handige links')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-link-45deg text-boels"></i> Handige links op het dashboard</h4>
</div>
<p class="text-muted">Links naar rekentools (bv. de generator-tool), documenten of externe sites.
    Ze verschijnen voor <strong>alle ingelogde medewerkers</strong> in het rechterpaneel van het dashboard, gegroepeerd per categorie.</p>

{{-- Nieuwe link toevoegen --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <strong class="d-block mb-2">Nieuwe link toevoegen</strong>
        <form method="POST" action="{{ route('admin.quick-links.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2"><label class="form-label small mb-0">Titel *</label>
                <input name="title" class="form-control form-control-sm" required maxlength="100" placeholder="Generator rekentool"></div>
            <div class="col-md-3"><label class="form-label small mb-0">URL *</label>
                <input name="url" class="form-control form-control-sm" required maxlength="500" placeholder="https://…"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Categorie</label>
                <input name="category" list="catList" class="form-control form-control-sm" maxlength="50" placeholder="Rekentools"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Icoon (bootstrap)</label>
                <input name="icon" class="form-control form-control-sm" maxlength="50" placeholder="bi-calculator"></div>
            <div class="col-md-2"><label class="form-label small mb-0">Omschrijving</label>
                <input name="description" class="form-control form-control-sm" maxlength="255"></div>
            <div class="col-md-1"><button class="btn btn-boels btn-sm w-100"><i class="bi bi-plus-lg"></i> Toevoegen</button></div>
        </form>
        <datalist id="catList">
            @foreach($categories as $c)<option value="{{ $c }}">@endforeach
            <option value="Rekentools"><option value="Documenten"><option value="Links">
        </datalist>
        <small class="text-muted">Iconen: zoek een naam op <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a> (bv. <code>bi-calculator</code>, <code>bi-file-earmark-pdf</code>, <code>bi-lightning-charge</code>).</small>
    </div>
</div>

{{-- Bestaande links --}}
@if($links->isEmpty())
    <div class="alert alert-info">Nog geen links. Voeg hierboven de eerste toe — bijvoorbeeld de generator-rekentool.</div>
@else
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th style="width:16%">Titel</th><th style="width:22%">URL</th>
                <th style="width:12%">Categorie</th><th style="width:11%">Icoon</th>
                <th style="width:18%">Omschrijving</th><th style="width:7%">Volgorde</th>
                <th style="width:6%">Actief</th><th style="width:8%"></th>
            </tr></thead>
            <tbody>
            @foreach($links as $link)
                @php($fid = 'upd-'.$link->id)
                <tr>
                    <td><input form="{{ $fid }}" name="title" value="{{ $link->title }}" class="form-control form-control-sm" required maxlength="100"></td>
                    <td><input form="{{ $fid }}" name="url" value="{{ $link->url }}" class="form-control form-control-sm" required maxlength="500"></td>
                    <td><input form="{{ $fid }}" name="category" value="{{ $link->category }}" list="catList" class="form-control form-control-sm" maxlength="50"></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="{{ $link->icon ?: 'bi-link-45deg' }}"></i></span>
                            <input form="{{ $fid }}" name="icon" value="{{ $link->icon }}" class="form-control" maxlength="50">
                        </div>
                    </td>
                    <td><input form="{{ $fid }}" name="description" value="{{ $link->description }}" class="form-control form-control-sm" maxlength="255"></td>
                    <td><input form="{{ $fid }}" name="sort_order" type="number" value="{{ $link->sort_order }}" class="form-control form-control-sm"></td>
                    <td class="text-center">
                        <input form="{{ $fid }}" type="hidden" name="active" value="0">
                        <input form="{{ $fid }}" type="checkbox" name="active" value="1" class="form-check-input" @checked($link->active)>
                    </td>
                    <td class="text-nowrap">
                        <button form="{{ $fid }}" class="btn btn-outline-success btn-sm" title="Opslaan"><i class="bi bi-check-lg"></i></button>
                        <button form="del-{{ $link->id }}" class="btn btn-outline-danger btn-sm" title="Verwijderen"
                                onclick="return confirm('Link &quot;{{ $link->title }}&quot; verwijderen?');"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Formulieren buiten de tabel (een <form> mag niet ín een <tr>) --}}
@foreach($links as $link)
    <form id="upd-{{ $link->id }}" method="POST" action="{{ route('admin.quick-links.update', $link) }}">@csrf @method('PUT')</form>
    <form id="del-{{ $link->id }}" method="POST" action="{{ route('admin.quick-links.destroy', $link) }}">@csrf @method('DELETE')</form>
@endforeach
@endif
@endsection
