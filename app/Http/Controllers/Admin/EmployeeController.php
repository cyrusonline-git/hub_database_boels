<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(Employee::query()->withTrashed(), $request);

        // Voor de bulk-grant knop: tel hoeveel van deze filterresultaten nog géén
        // login-account hebben EN een email-adres hebben.
        $candidates = $this->countLoginCandidates($request);

        $filters = [
            'depots'    => Employee::distinct()->whereNotNull('depot')->orderBy('depot')->pluck('depot'),
            'areas'     => Employee::distinct()->whereNotNull('area')->orderBy('area')->pluck('area'),
            'countries' => Employee::distinct()->whereNotNull('country')->orderBy('country')->pluck('country'),
            'functions' => Employee::distinct()->whereNotNull('function')->orderBy('function')->pluck('function'),
        ];

        $employees = $query->orderBy('name')->paginate(50)->withQueryString();

        $hasFilter = $request->hasAny(['q', 'depot', 'area', 'country', 'function', 'status']);

        return view('admin.employees.index', compact('employees', 'filters', 'candidates', 'hasFilter'));
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

    /**
     * Maakt User-accounts aan voor alle gefilterde medewerkers zonder bestaand login.
     * - status = pending_activation
     * - allowed_areas/depots/countries gekopieerd uit employee
     * - activation_token gegenereerd + mail verstuurd
     */
    public function bulkGrantLogin(Request $request)
    {
        $request->validate([
            'default_role' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($request->input('default_role'));
        $query = $this->applyFilters(Employee::query(), $request); // zonder withTrashed
        $employees = $query->whereNull('deleted_at')->get();

        $created = 0;
        $skippedExisting = 0;
        $skippedNoEmail = 0;
        $mailFailed = 0;
        $createdEmails = [];

        foreach ($employees as $employee) {
            if (! $employee->email) {
                $skippedNoEmail++;
                continue;
            }
            if (User::where('email', $employee->email)->withTrashed()->exists()) {
                $skippedExisting++;
                continue;
            }

            $token = Str::random(40);
            $user = DB::transaction(function () use ($employee, $token, $role) {
                $u = User::create([
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'password' => Str::random(40), // placeholder; wordt overschreven bij activatie
                    'employee_id' => $employee->id,
                    'is_super_admin' => false,
                    'active' => false,
                    'status' => User::STATUS_PENDING,
                    'allowed_areas' => $employee->area ? [$employee->area] : null,
                    'allowed_depots' => $employee->depot ? [$employee->depot] : null,
                    'allowed_countries' => $employee->country ? [$employee->country] : null,
                    'activation_token' => $token,
                    'activation_token_expires_at' => now()->addDays(7),
                ]);
                $u->roles()->attach($role->id);
                return $u;
            });

            try {
                Mail::to($user->email)->send(new AccountActivationMail($user, url('/activate/'.$token)));
            } catch (\Throwable $e) {
                Log::warning('Activatie-mail kon niet verstuurd worden', ['email' => $user->email, 'err' => $e->getMessage()]);
                $mailFailed++;
            }

            $created++;
            $createdEmails[] = $user->email;
        }

        $msg = "Login-accounts aangemaakt: $created. ";
        if ($skippedExisting) $msg .= "Reeds bestaand: $skippedExisting. ";
        if ($skippedNoEmail) $msg .= "Zonder email overgeslagen: $skippedNoEmail. ";
        if ($mailFailed) $msg .= "Mail niet verstuurd: $mailFailed (zie logs). ";

        return redirect()->route('admin.employees.index', $request->query())->with('status', trim($msg));
    }

    // =========== private ===========

    private function applyFilters($query, Request $request)
    {
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

        return $query;
    }

    private function countLoginCandidates(Request $request): int
    {
        $q = $this->applyFilters(Employee::query(), $request)
            ->whereNull('deleted_at')
            ->whereNotNull('email')
            ->whereNotIn('email', User::query()->withTrashed()->select('email'));

        return $q->count();
    }
}
