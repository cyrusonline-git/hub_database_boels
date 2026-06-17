<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query()->withTrashed();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%")
                  ->orWhere('employee_number', 'like', "%$q%");
            });
        }

        foreach (['depot', 'area', 'country', 'function'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('active', true)->whereNull('deleted_at');
            } elseif ($request->input('status') === 'inactive') {
                $query->where(fn ($w) => $w->where('active', false)->orWhereNotNull('deleted_at'));
            }
        }

        // Filteropties (alleen unieke waarden uit DB)
        $filters = [
            'depots'    => Employee::distinct()->whereNotNull('depot')->orderBy('depot')->pluck('depot'),
            'areas'     => Employee::distinct()->whereNotNull('area')->orderBy('area')->pluck('area'),
            'countries' => Employee::distinct()->whereNotNull('country')->orderBy('country')->pluck('country'),
            'functions' => Employee::distinct()->whereNotNull('function')->orderBy('function')->pluck('function'),
        ];

        $employees = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('admin.employees.index', compact('employees', 'filters'));
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.form', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'employee_number' => ['required','string','max:50'],
            'name'            => ['required','string','max:200'],
            'email'           => ['nullable','email','max:190'],
            'phone'           => ['nullable','string','max:50'],
            'department_id'   => ['nullable','exists:departments,id'],
            'function'        => ['nullable','string','max:150'],
            'area'            => ['nullable','string','max:100'],
            'country'         => ['nullable','string','max:100'],
            'city'            => ['nullable','string','max:100'],
            'region'          => ['nullable','string','max:100'],
            'depot'           => ['nullable','string','max:100'],
            'manager'         => ['nullable','string','max:200'],
            'cost_center'     => ['nullable','string','max:50'],
            'active'          => ['sometimes','boolean'],
        ]);
        $employee->update($data);
        return redirect()->route('admin.employees.index')->with('status', 'Medewerker bijgewerkt.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return back()->with('status', 'Medewerker op inactief gezet.');
    }

    public function restore(int $id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $employee->restore();
        $employee->update(['active' => true]);
        return back()->with('status', 'Medewerker hersteld.');
    }
}
