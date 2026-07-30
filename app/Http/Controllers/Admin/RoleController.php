<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('application')
            ->withCount(['permissions','users'])
            ->when($request->filled('app'), fn ($q) => $request->input('app') === 'platform'
                ? $q->whereNull('application_id')
                : $q->where('application_id', $request->input('app')))
            ->orderByRaw('application_id is not null')->orderBy('application_id')->orderBy('name')
            ->get();
        $apps = Application::orderBy('name')->get(['id','name']);
        return view('admin.roles.index', compact('roles', 'apps'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(),
            'permissions' => Permission::with('application')->orderBy('application_id')->orderBy('key')->get(),
            'apps' => Application::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);
        $role = Role::create($data);
        $role->permissions()->sync($request->input('permissions', []));
        $role->launcherApplications()->sync($request->input('launcher_apps', []));
        return redirect()->route('admin.roles.index')->with('status', 'Rol aangemaakt.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissions' => Permission::with('application')->orderBy('application_id')->orderBy('key')->get(),
            'apps' => Application::orderBy('name')->get(['id','name']),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);
        $role->update($data);
        $role->permissions()->sync($request->input('permissions', []));
        $role->launcherApplications()->sync($request->input('launcher_apps', []));
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
        $unique = Rule::unique('roles', 'name')
            ->where('application_id', $request->input('application_id') ?: null);
        if ($role) {
            $unique->ignore($role->id);
        }

        return $request->validate([
            'name' => ['required','string','max:100', $unique],
            'application_id' => ['nullable','integer','exists:applications,id'],
            'description' => ['nullable','string'],
            'permissions' => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
            'launcher_apps' => ['array'],
            'launcher_apps.*' => ['integer','exists:applications,id'],
        ]);
    }
}
