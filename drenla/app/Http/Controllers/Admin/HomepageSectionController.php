<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        $sections = HomepageSection::with('mediaAsset')
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get();

        return view('admin.homepage.index', compact('sections'));
    }

    public function create(): View
    {
        return view('admin.homepage.form', [
            'section' => new HomepageSection,
            'mediaAssets' => MediaAsset::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        HomepageSection::create($this->validatedData($request));

        return redirect()->route('admin.homepage.index')->with('status', 'Homepage section created.');
    }

    public function edit(HomepageSection $section): View
    {
        $section->load('mediaAsset');

        return view('admin.homepage.form', [
            'section' => $section,
            'mediaAssets' => MediaAsset::orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, HomepageSection $section): RedirectResponse
    {
        $section->update($this->validatedData($request, $section));

        return redirect()->route('admin.homepage.index')->with('status', 'Homepage section updated.');
    }

    public function destroy(HomepageSection $section): RedirectResponse
    {
        $section->delete();

        return redirect()->route('admin.homepage.index')->with('status', 'Homepage section deleted.');
    }

    private function validatedData(Request $request, ?HomepageSection $section = null): array
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:homepage_sections,key,'.($section?->id ?? 'null')],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'media_upload' => ['nullable', 'image', 'max:10240'],
            'highlights_text' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $highlights = collect(explode("\n", (string) ($validated['highlights_text'] ?? '')))
            ->map(fn (string $highlight) => trim($highlight))
            ->filter()
            ->values()
            ->all();

        $mediaAssetId = $validated['media_asset_id'] ?? null;

        if ($request->hasFile('media_upload')) {
            $asset = $this->storeOrUpdateMediaAsset(
                uploadedFile: $request->file('media_upload'),
                existingId: null,
                title: $request->string('title')->toString(),
                altText: null,
                folder: 'homepage',
            );

            $mediaAssetId = $asset?->id;
        }

        return Arr::except($validated, ['media_upload', 'highlights_text']) + [
            'media_asset_id' => $mediaAssetId,
            'payload' => count($highlights) > 0 ? ['highlights' => $highlights] : null,
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
