<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::orderBy('sort_order')->orderBy('name')->paginate(50);
        return view('admin.applications.index', compact('applications'));
    }

    public function create()
    {
        return view('admin.applications.form', ['application' => new Application()]);
    }

    public function store(Request $request)
    {
        Application::create($this->normalize($this->validateApp($request)));
        return redirect()->route('admin.applications.index')->with('status', 'Applicatie toegevoegd.');
    }

    public function edit(Application $application)
    {
        return view('admin.applications.form', [
            'application' => $application,
            'appRoles' => \App\Models\Role::where('application_id', $application->id)
                ->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Application $application)
    {
        $application->update($this->normalize($this->validateApp($request, $application)));
        return redirect()->route('admin.applications.index')->with('status', 'Applicatie bijgewerkt.');
    }

    public function destroy(Application $application)
    {
        $application->delete();
        return back()->with('status', 'Applicatie verwijderd.');
    }

    /**
     * Haalt de rollen op die de app zelf publiceert op {url}/core-roles.php
     * en maakt ze aan als app-rollen in CORE. Idempotent.
     */
    public function importRoles(Application $application)
    {
        if (! $application->url) {
            return back()->withErrors('Deze app heeft geen URL ingesteld — vul die eerst in.');
        }

        $result = $this->fetchAndImportRoles($application, $application->url);
        if (is_string($result)) {
            return back()->withErrors($result);
        }

        return back()->with('status', "Rollen geïmporteerd uit de app: {$result['created']} nieuw, {$result['existing']} bestonden al.");
    }

    /**
     * Nieuwe app koppelen met alleen een URL: leest {url}/core-roles.php,
     * maakt de applicatie (launcher-tegel) aan én importeert de rollen.
     */
    public function registerFromUrl(Request $request)
    {
        $request->validate(['url' => ['required', 'url', 'max:255']]);
        $url = rtrim($request->input('url'), '/');

        $data = $this->fetchEndpoint($url);
        if (is_string($data)) {
            return back()->withErrors($data.' Controleer of de app core-roles.php publiceert (zie migratie-prompt).');
        }

        $slug = \Illuminate\Support\Str::slug($data['app'] ?? explode('.', parse_url($url, PHP_URL_HOST))[0]);
        $name = $data['name'] ?? ucfirst($slug);

        $application = Application::withTrashed()->firstOrCreate(
            ['slug' => $slug],
            ['name' => \Illuminate\Support\Str::limit($name, 150), 'url' => $url, 'active' => true],
        );
        if ($application->trashed()) $application->restore();
        if (! $application->url) $application->update(['url' => $url]);

        $result = $this->fetchAndImportRoles($application, $url);
        $rolesMsg = is_string($result)
            ? 'Rollen konden niet gelezen worden.'
            : "{$result['created']} rol(len) geïmporteerd, {$result['existing']} bestonden al.";

        return redirect()->route('admin.applications.edit', $application)->with('status',
            ($application->wasRecentlyCreated ? "App \"$name\" aangemaakt met launcher-tegel. " : "App \"$name\" bestond al — bijgewerkt. ").$rolesMsg
            .' Let op: voor SSO moet het subdomein nog aangemeld worden in CORE (SANCTUM_STATEFUL_DOMAINS).');
    }

    /** Haalt {url}/core-roles.php op; geeft array terug of een foutmelding (string). */
    private function fetchEndpoint(string $url): array|string
    {
        $endpoint = rtrim($url, '/').'/core-roles.php';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $body) {
            return "Kon $endpoint niet ophalen (HTTP $code).";
        }
        $data = json_decode($body, true);
        if (! is_array($data) || ! isset($data['roles']) || ! is_array($data['roles'])) {
            return 'Onverwacht antwoord van de app — verwacht JSON met een "roles"-lijst.';
        }
        return $data;
    }

    /** Importeert de rollen van het endpoint in CORE; array met tellingen of foutmelding (string). */
    private function fetchAndImportRoles(Application $application, string $url): array|string
    {
        $data = $this->fetchEndpoint($url);
        if (is_string($data)) {
            return $data;
        }

        $created = 0;
        $existing = 0;
        foreach ($data['roles'] as $r) {
            $name = trim((string) ($r['name'] ?? $r['slug'] ?? ''));
            if ($name === '') continue;
            $slug = \Illuminate\Support\Str::slug($r['slug'] ?? $name);

            $role = \App\Models\Role::withTrashed()->firstOrCreate(
                ['application_id' => $application->id, 'slug' => $slug],
                ['name' => \Illuminate\Support\Str::limit($name, 100),
                 'description' => $r['description'] ?? null],
            );
            if ($role->trashed()) $role->restore();
            $role->launcherApplications()->syncWithoutDetaching([$application->id]);
            $role->wasRecentlyCreated ? $created++ : $existing++;
        }

        return compact('created', 'existing');
    }

    private function validateApp(Request $request, ?Application $app = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:150'],
            'slug' => ['nullable','string','max:100', 'unique:applications,slug'.($app ? ','.$app->id : '')],
            'description' => ['nullable','string'],
            'url' => ['nullable','url','max:255'],
            'icon' => ['nullable','string','max:100'],
            'color' => ['nullable','string','max:20'],
            'sort_order' => ['nullable','integer'],
            'active' => ['sometimes','boolean'],
            'restricted_to_areas' => ['nullable','string'],
            'restricted_to_depots' => ['nullable','string'],
            'restricted_to_countries' => ['nullable','string'],
        ]);
    }

    private function normalize(array $data): array
    {
        foreach (['restricted_to_areas', 'restricted_to_depots', 'restricted_to_countries'] as $k) {
            if (! array_key_exists($k, $data)) continue;
            $raw = trim((string) $data[$k]);
            $data[$k] = $raw === ''
                ? null
                : array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return $data;
    }
}
