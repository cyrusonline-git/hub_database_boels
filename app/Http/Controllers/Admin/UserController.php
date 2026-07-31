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

    public function create(Request $request)
    {
        $user = new User();

        // Vooringevuld vanaf de Medewerkers-pagina ("Maak login"-knop)
        if ($request->filled('employee_id') && ($e = Employee::find($request->integer('employee_id')))) {
            $user->employee_id = $e->id;
            $user->name = $e->name;
            $user->email = $e->email;
            $user->allowed_areas = $e->area ? [$e->area] : null;
            $user->allowed_depots = $e->depot ? [$e->depot] : null;
            $user->allowed_countries = $e->country ? [$e->country] : null;
        }

        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::with('application')->orderByRaw('application_id is not null')->orderBy('application_id')->orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data = $this->normalizeAccessLists($data);
        $data['password'] = Hash::make($data['password']);

        // Bestond er een (zacht) verwijderd account met dit e-mailadres?
        // Dan herstellen en bijwerken i.p.v. blokkeren met "al in gebruik".
        $trashed = User::onlyTrashed()->where('email', $data['email'])->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->update($data);
            $trashed->syncRoles($request->input('roles', []));
            return redirect()->route('admin.users.index')
                ->with('status', 'Er bestond nog een verwijderd account met dit e-mailadres — dat is hersteld en bijgewerkt met de nieuwe gegevens.');
        }

        $user = User::create($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('admin.users.index')->with('status', 'Gebruiker aangemaakt.');
    }

    /** E-mail uniek, maar zacht-verwijderde accounts tellen niet mee (die herstelt store()) */
    private function emailUniqueRule(?User $user)
    {
        $rule = \Illuminate\Validation\Rule::unique('users', 'email')->withoutTrashed();
        if ($user) {
            $rule->ignore($user->id);
        }
        return $rule;
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::with('application')->orderByRaw('application_id is not null')->orderBy('application_id')->orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);
        $data = $this->normalizeAccessLists($data);
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
            'email' => ['required','email','max:190', $this->emailUniqueRule($user)],
            'password' => [$user ? 'nullable' : 'required','string','min:8'],
            'employee_id' => ['nullable','exists:employees,id'],
            'is_super_admin' => ['sometimes','boolean'],
            'active' => ['sometimes','boolean'],
            'status' => ['sometimes', 'in:active,pending_activation,disabled'],
            'allowed_areas' => ['nullable','string'],
            'allowed_depots' => ['nullable','string'],
            'allowed_countries' => ['nullable','string'],
            'roles' => ['array'],
            'roles.*' => ['integer','exists:roles,id'],
        ]);
    }

    /**
     * Komma-gescheiden invoer -> array. Lege string -> null (= geen scope-restrictie).
     */
    private function normalizeAccessLists(array $data): array
    {
        foreach (['allowed_areas', 'allowed_depots', 'allowed_countries'] as $key) {
            if (! array_key_exists($key, $data)) continue;
            $raw = trim((string) $data[$key]);
            if ($raw === '') {
                $data[$key] = null;
                continue;
            }
            $data[$key] = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return $data;
    }
}
