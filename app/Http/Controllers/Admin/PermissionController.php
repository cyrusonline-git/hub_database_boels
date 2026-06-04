<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('application')->orderBy('application_id')->orderBy('key')->paginate(50);
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.form', [
            'permission' => new Permission(),
            'applications' => Application::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Permission::create($this->validatePerm($request));
        return redirect()->route('admin.permissions.index')->with('status', 'Permissie aangemaakt.');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.form', [
            'permission' => $permission,
            'applications' => Application::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $permission->update($this->validatePerm($request, $permission));
        return redirect()->route('admin.permissions.index')->with('status', 'Permissie bijgewerkt.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return back()->with('status', 'Permissie verwijderd.');
    }

    private function validatePerm(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'application_id' => ['nullable','exists:applications,id'],
            'key' => ['required','string','max:150'],
            'name' => ['required','string','max:150'],
            'description' => ['nullable','string'],
        ]);
    }
}
