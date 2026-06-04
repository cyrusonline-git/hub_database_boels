<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index()
    {
        $fields = CustomField::orderBy('entity')->orderBy('sort_order')->paginate(50);
        return view('admin.custom_fields.index', compact('fields'));
    }

    public function create() { return view('admin.custom_fields.form', ['field' => new CustomField()]); }
    public function edit(CustomField $custom_field) { return view('admin.custom_fields.form', ['field' => $custom_field]); }

    public function store(Request $request)
    {
        CustomField::create($this->v($request));
        return redirect()->route('admin.custom-fields.index')->with('status','Custom field toegevoegd.');
    }

    public function update(Request $request, CustomField $custom_field)
    {
        $custom_field->update($this->v($request, $custom_field));
        return redirect()->route('admin.custom-fields.index')->with('status','Custom field bijgewerkt.');
    }

    public function destroy(CustomField $custom_field)
    {
        $custom_field->delete();
        return back()->with('status','Custom field verwijderd.');
    }

    private function v(Request $r, ?CustomField $cf = null): array
    {
        $data = $r->validate([
            'entity'     => ['required','string','max:100'],
            'key'        => ['required','string','max:100'],
            'label'      => ['required','string','max:150'],
            'type'       => ['required','in:text,number,date,boolean,select'],
            'options_raw'=> ['nullable','string'],
            'required'   => ['sometimes','boolean'],
            'sort_order' => ['nullable','integer'],
        ]);

        if ($r->input('type') === 'select' && $r->filled('options_raw')) {
            $data['options'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $r->input('options_raw')))));
        }
        unset($data['options_raw']);
        return $data;
    }
}
