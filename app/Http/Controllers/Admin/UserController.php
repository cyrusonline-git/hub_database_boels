<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Mail\AccountActivationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(25);
        $pendingCount = User::where('status', User::STATUS_PENDING)->count();
        return view('admin.users.index', compact('users', 'pendingCount'));
    }

    /**
     * Stuurt (opnieuw) een inlog-mail naar één gebruiker: verse
     * activatielink (7 dagen geldig) waarmee hij zelf een wachtwoord
     * kiest. Kan altijd — ook voor een al actief account (dan werkt de
     * link als "nieuw wachtwoord instellen"; het account blijft gewoon
     * werken totdat de link gebruikt wordt).
     */
    public function sendLoginMail(User $user)
    {
        $token = Str::random(40);
        $user->update([
            'activation_token' => $token,
            'activation_token_expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, url('/activate/'.$token)));
            $user->update(['activation_mail_sent_at' => now()]);
            return back()->with('status', "Inlog-mail verstuurd naar {$user->email} — daarmee kiest de medewerker zelf een wachtwoord (link 7 dagen geldig).");
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors("Inlog-mail naar {$user->email} kon NIET verstuurd worden (mailserverfout). Probeer het later opnieuw of laat de medewerker \"Wachtwoord vergeten?\" gebruiken op de loginpagina.");
        }
    }

    /**
     * Stuurt in ÉÉN keer een activatiemail (CORE-login) naar alle
     * gebruikers die nog wachten op activatie. Tokens worden eerst
     * ververst (7 dagen geldig), dus dit mag ook later of opnieuw.
     */
    public function mailPending()
    {
        $wachtend = User::where('status', User::STATUS_PENDING)->get();
        if ($wachtend->isEmpty()) {
            return back()->with('status', 'Er zijn geen gebruikers die op activatie wachten.');
        }

        $verstuurd = 0;
        $mislukt = [];
        foreach ($wachtend as $user) {
            $token = Str::random(40);
            $user->update([
                'activation_token' => $token,
                'activation_token_expires_at' => now()->addDays(7),
            ]);
            try {
                Mail::to($user->email)->send(new AccountActivationMail($user, url('/activate/'.$token)));
                $user->update(['activation_mail_sent_at' => now()]);
                $verstuurd++;
            } catch (\Throwable $e) {
                report($e);
                $mislukt[] = $user->email;
            }
        }

        $msg = "Activatiemail verstuurd naar $verstuurd gebruiker(s) — daarmee kiezen ze zelf hun CORE-wachtwoord (link 7 dagen geldig).";
        if ($mislukt) {
            $msg .= ' LET OP: mislukt voor '.implode(', ', array_slice($mislukt, 0, 10))
                .(count($mislukt) > 10 ? '…' : '').' — die kunnen "Wachtwoord vergeten?" gebruiken.';
        }
        return back()->with('status', $msg);
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

        // Wachtwoord leeg gelaten? Dan krijgt de medewerker een activatiemail
        // en kiest hij zelf zijn wachtwoord (zoals bij de bulk-actie).
        $activationToken = null;
        if (empty($data['password'])) {
            $activationToken = Str::random(40);
            $data['password'] = Hash::make(Str::random(40)); // placeholder
            $data['status'] = User::STATUS_PENDING;
            $data['active'] = false;
            $data['activation_token'] = $activationToken;
            $data['activation_token_expires_at'] = now()->addDays(7);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Bestond er een (zacht) verwijderd account met dit e-mailadres?
        // Dan herstellen en bijwerken i.p.v. blokkeren met "al in gebruik".
        $trashed = User::onlyTrashed()->where('email', $data['email'])->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->update($data);
            $trashed->syncRoles($request->input('roles', []));
            $mailMsg = $this->sendActivationIfNeeded($trashed, $activationToken);
            return redirect()->route('admin.users.index')
                ->with('status', 'Er bestond nog een verwijderd account met dit e-mailadres — dat is hersteld en bijgewerkt.'.$mailMsg);
        }

        $user = User::create($data);
        $user->syncRoles($request->input('roles', []));
        $mailMsg = $this->sendActivationIfNeeded($user, $activationToken);

        return redirect()->route('admin.users.index')->with('status', 'Gebruiker aangemaakt.'.$mailMsg);
    }

    private function sendActivationIfNeeded(User $user, ?string $token): string
    {
        if (! $token) {
            return '';
        }
        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, url('/activate/'.$token)));
            $user->update(['activation_mail_sent_at' => now()]);
            return ' Activatiemail verstuurd naar '.$user->email.' — daarmee kiest de medewerker zelf een wachtwoord.';
        } catch (\Throwable $e) {
            report($e);
            return ' LET OP: activatiemail kon niet verstuurd worden — gebruik "Wachtwoord vergeten?" op de loginpagina als alternatief.';
        }
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
            'password' => ['nullable','string','min:8'],
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
