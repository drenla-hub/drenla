<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CaseStudyController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        $caseStudies = CaseStudy::with('mediaAsset')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.case-studies.index', compact('caseStudies'));
    }

    public function create(): View
    {
        return view('admin.case-studies.form', ['caseStudy' => new CaseStudy]);
    }

    public function store(Request $request): RedirectResponse
    {
        CaseStudy::create($this->validatedData($request));

        return redirect()->route('admin.case-studies.index')->with('status', 'Case study created.');
    }

    public function edit(CaseStudy $caseStudy): View
    {
        $caseStudy->load('mediaAsset');

        return view('admin.case-studies.form', compact('caseStudy'));
    }

    public function update(Request $request, CaseStudy $caseStudy): RedirectResponse
    {
        $caseStudy->update($this->validatedData($request, $caseStudy));

        return redirect()->route('admin.case-studies.index')->with('status', 'Case study updated.');
    }

    public function destroy(CaseStudy $caseStudy): RedirectResponse
    {
        $caseStudy->delete();

        return redirect()->route('admin.case-studies.index')->with('status', 'Case study deleted.');
    }

    private function validatedData(Request $request, ?CaseStudy $caseStudy = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:case_studies,slug,'.($caseStudy?->id ?? 'null')],
            'client_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:10240'],
            'cover_image_title' => ['nullable', 'string', 'max:255'],
            'cover_image_alt_text' => ['nullable', 'string', 'max:255'],
            'chapters' => ['nullable', 'array'],
            'chapters.*.num' => ['nullable', 'string', 'max:10'],
            'chapters.*.title' => ['nullable', 'string', 'max:255'],
            'chapters.*.paragraphs' => ['nullable', 'array'],
            'chapters.*.paragraphs.*' => ['nullable', 'string'],
            'chapters.*.images' => ['nullable', 'array'],
            'chapters.*.images.*.layout' => ['nullable', 'in:full,2col,3col'],
            'chapters.*.images.*.items' => ['nullable', 'array'],
            'chapters.*.images.*.items.*.media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'chapters.*.images.*.items.*.title' => ['nullable', 'string', 'max:255'],
            'chapters.*.images.*.items.*.alt_text' => ['nullable', 'string', 'max:255'],
            'chapters.*.images.*.items.*.caption' => ['nullable', 'string', 'max:255'],
            'chapters.*.images.*.items.*.file' => ['nullable', 'image', 'max:10240'],
            'featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        return Arr::except($validated, [
            'cover_image',
            'cover_image_title',
            'cover_image_alt_text',
            'chapters',
        ]) + [
            'media_asset_id' => $this->syncCoverImage($request, $caseStudy?->media_asset_id),
            'content_blocks' => $this->syncChapters($request),
            'gallery' => [],
            'featured' => $request->boolean('featured'),
        ];
    }

    private function syncCoverImage(Request $request, ?int $existingId = null): ?int
    {
        $asset = $this->storeOrUpdateMediaAsset(
            uploadedFile: $request->file('cover_image'),
            existingId: $existingId,
            title: $request->string('cover_image_title')->toString(),
            altText: $request->string('cover_image_alt_text')->toString(),
            folder: 'case-studies',
        );

        return $asset?->id;
    }

    private function syncChapters(Request $request): array
    {
        $chapters = [];

        foreach ($request->input('chapters', []) as $chapterIndex => $chapter) {
            $title = trim((string) ($chapter['title'] ?? ''));
            $num = trim((string) ($chapter['num'] ?? ''));
            $paragraphs = collect($chapter['paragraphs'] ?? [])
                ->map(fn (mixed $paragraph) => trim((string) $paragraph))
                ->filter()
                ->values()
                ->all();

            $images = [];

            foreach (($chapter['images'] ?? []) as $groupIndex => $group) {
                $layout = $group['layout'] ?? 'full';
                $items = [];

                foreach (($group['items'] ?? []) as $itemIndex => $item) {
                    $asset = $this->storeOrUpdateMediaAsset(
                        uploadedFile: $request->file("chapters.$chapterIndex.images.$groupIndex.items.$itemIndex.file"),
                        existingId: $this->nullableInt($item['media_asset_id'] ?? null),
                        title: $item['title'] ?? null,
                        altText: $item['alt_text'] ?? null,
                        folder: 'case-studies',
                    );

                    $caption = trim((string) ($item['caption'] ?? ''));

                    if ($asset === null && $caption === '') {
                        continue;
                    }

                    $items[] = [
                        'media_asset_id' => $asset?->id,
                        'caption' => $caption !== '' ? $caption : null,
                    ];
                }

                $requiredCount = match ($layout) {
                    '2col' => 2,
                    '3col' => 3,
                    default => 1,
                };

                if (count($items) > 0) {
                    $images[] = [
                        'layout' => $layout,
                        'items' => array_slice($items, 0, $requiredCount),
                    ];
                }
            }

            if ($title === '' && $num === '' && count($paragraphs) === 0 && count($images) === 0) {
                continue;
            }

            $chapters[] = [
                'num' => $num !== '' ? $num : (string) (count($chapters) + 1),
                'title' => $title !== '' ? $title : null,
                'paragraphs' => $paragraphs,
                'images' => $images,
            ];
        }

        return $chapters;
    }
}
