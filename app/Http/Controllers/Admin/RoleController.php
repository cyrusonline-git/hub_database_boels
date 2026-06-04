<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions','users'])->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(),
            'permissions' => Permission::with('application')->orderBy('application_id')->orderBy('key')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $role = Role::create($data);
        $role->permissions()->sync($request->input('permissions', []));
        return redirect()->route('admin.roles.index')->with('status', 'Rol aangemaakt.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissions' => Permission::with('application')->orderBy('application_id')->orderBy('key')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);
        $role->update($data);
        $role->permissions()->sync($request->input('permissions', []));
        return redirect()->route('admin.roles.index')->with('status', 'Rol bijgewerkt.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->withErrors('Systeemrol kan niet verwijderd worden.');
        }
        $role->delete();
        return back()->with('status', 'Rol verwijderd.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:100', 'unique:roles,name'.($role ? ','.$role->id : '')],
            'description' => ['nullable','string'],
            'permissions' => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);
    }
}
