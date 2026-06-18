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
        return view('admin.applications.form', compact('application'));
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
