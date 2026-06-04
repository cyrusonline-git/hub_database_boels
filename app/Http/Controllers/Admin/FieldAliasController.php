<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldAlias;
use Illuminate\Http\Request;

class FieldAliasController extends Controller
{
    public function index()
    {
        $aliases = FieldAlias::orderBy('entity')->orderBy('alias')->paginate(50);
        return view('admin.field_aliases.index', compact('aliases'));
    }

    public function create()  { return view('admin.field_aliases.form', ['alias' => new FieldAlias()]); }
    public function edit(FieldAlias $field_alias) { return view('admin.field_aliases.form', ['alias' => $field_alias]); }

    public function store(Request $request)
    {
        FieldAlias::create($this->v($request) + ['created_by' => $request->user()->id]);
        return redirect()->route('admin.field-aliases.index')->with('status','Alias toegevoegd.');
    }

    public function update(Request $request, FieldAlias $field_alias)
    {
        $field_alias->update($this->v($request, $field_alias));
        return redirect()->route('admin.field-aliases.index')->with('status','Alias bijgewerkt.');
    }

    public function destroy(FieldAlias $field_alias)
    {
        $field_alias->delete();
        return back()->with('status','Alias verwijderd.');
    }

    private function v(Request $r, ?FieldAlias $a = null): array
    {
        return $r->validate([
            'entity' => ['required','string','max:100'],
            'alias'  => ['required','string','max:190'],
            'field'  => ['required','string','max:100'],
        ]);
    }
}
