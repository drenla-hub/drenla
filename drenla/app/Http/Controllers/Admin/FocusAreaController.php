<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FocusArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FocusAreaController extends Controller
{
    public function index(): View
    {
        $focusAreas = FocusArea::orderBy('sort_order')->orderBy('title')->get();

        return view('admin.focus-areas.index', compact('focusAreas'));
    }

    public function create(): View
    {
        return view('admin.focus-areas.form', ['focusArea' => new FocusArea]);
    }

    public function store(Request $request): RedirectResponse
    {
        FocusArea::create($this->validatedData($request));

        return redirect()->route('admin.focus-areas.index')->with('status', 'Focus area created.');
    }

    public function edit(FocusArea $focusArea): View
    {
        return view('admin.focus-areas.form', compact('focusArea'));
    }

    public function update(Request $request, FocusArea $focusArea): RedirectResponse
    {
        $focusArea->update($this->validatedData($request, $focusArea));

        return redirect()->route('admin.focus-areas.index')->with('status', 'Focus area updated.');
    }

    public function destroy(FocusArea $focusArea): RedirectResponse
    {
        $focusArea->delete();

        return redirect()->route('admin.focus-areas.index')->with('status', 'Focus area deleted.');
    }

    private function validatedData(Request $request, ?FocusArea $focusArea = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:focus_areas,slug,'.($focusArea?->id ?? 'null')],
            'summary' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]) + [
            'featured' => $request->boolean('featured'),
        ];
    }
}
