<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(25);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User(),
            'roles' => Role::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('admin.users.index')->with('status', 'Gebruiker aangemaakt.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('admin.users.index')->with('status', 'Gebruiker bijgewerkt.');
    }

    public function destroy(User $user)
    {
        if ($user->is_super_admin) {
            return back()->withErrors('Super Admin kan niet verwijderd worden.');
        }
        $user->delete();
        return back()->with('status', 'Gebruiker verwijderd.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:150'],
            'email' => ['required','email','max:190', 'unique:users,email'.($user ? ','.$user->id : '')],
            'password' => [$user ? 'nullable' : 'required','string','min:8'],
            'employee_id' => ['nullable','exists:employees,id'],
            'is_super_admin' => ['sometimes','boolean'],
            'active' => ['sometimes','boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer','exists:roles,id'],
        ]);
    }
}
