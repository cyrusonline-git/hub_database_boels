<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickLink;
use Illuminate\Http\Request;

class QuickLinkController extends Controller
{
    public function index()
    {
        $links = QuickLink::orderBy('category')->orderBy('sort_order')->orderBy('title')->get();
        $categories = $links->pluck('category')->filter()->unique()->values();

        return view('admin.quick_links.index', compact('links', 'categories'));
    }

    public function store(Request $request)
    {
        QuickLink::create($this->validated($request));

        return redirect()->route('admin.quick-links.index')->with('status', 'Link toegevoegd.');
    }

    public function update(Request $request, QuickLink $quickLink)
    {
        $quickLink->update($this->validated($request));

        return redirect()->route('admin.quick-links.index')->with('status', 'Link bijgewerkt.');
    }

    public function destroy(QuickLink $quickLink)
    {
        $quickLink->delete();

        return redirect()->route('admin.quick-links.index')->with('status', 'Link verwijderd.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['active'] = $request->boolean('active', true);

        return $data;
    }
}
