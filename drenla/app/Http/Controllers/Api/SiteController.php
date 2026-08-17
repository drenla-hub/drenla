<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\FocusArea;
use App\Models\HomepageSection;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function settings(): JsonResponse
    {
        return response()->json([
            'site_name' => SiteSetting::getValue('site_name', 'Drenla'),
            'contact_email' => SiteSetting::getValue('contact_email'),
            'primary_phone' => SiteSetting::getValue('primary_phone'),
            'office_location' => SiteSetting::getValue('office_location'),
            'footer_text' => SiteSetting::getValue('footer_text'),
            'navigation' => SiteSetting::getValue('navigation', []),
        ]);
    }

    public function home(): JsonResponse
    {
        $sections = HomepageSection::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (HomepageSection $section) => [
                'key' => $section->key,
                'eyebrow' => $section->eyebrow,
                'title' => $section->title,
                'body' => $section->body,
                'cta_label' => $section->cta_label,
                'cta_url' => $section->cta_url,
                'payload' => $section->payload ?? [],
            ]);

        return response()->json(['sections' => $sections]);
    }

    public function workIndex(): JsonResponse
    {
        return response()->json([
            'data' => CaseStudy::with('mediaAsset')
                ->published()
                ->orderByDesc('featured')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (CaseStudy $caseStudy) => $this->workPayload($caseStudy)),
        ]);
    }

    public function workShow(string $slug): JsonResponse
    {
        $caseStudy = CaseStudy::with('mediaAsset')->published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->workPayload($caseStudy, true)]);
    }

    public function insightsIndex(): JsonResponse
    {
        return response()->json([
            'data' => Article::published()
                ->where('type', 'insight')
                ->latest('published_at')
                ->get()
                ->map(fn (Article $article) => $this->articlePayload($article)),
        ]);
    }

    public function insightShow(string $slug): JsonResponse
    {
        $article = Article::published()
            ->where('type', 'insight')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $this->articlePayload($article, true)]);
    }

    public function resourcesIndex(): JsonResponse
    {
        return response()->json([
            'data' => Article::published()
                ->where('type', 'resource')
                ->latest('published_at')
                ->get()
                ->map(fn (Article $article) => $this->articlePayload($article)),
        ]);
    }

    public function focusAreasIndex(): JsonResponse
    {
        return response()->json([
            'data' => FocusArea::published()
                ->orderByDesc('featured')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (FocusArea $focusArea) => [
                    'title' => $focusArea->title,
                    'slug' => $focusArea->slug,
                    'summary' => $focusArea->summary,
                    'body' => $focusArea->body,
                ]),
        ]);
    }

    private function workPayload(CaseStudy $caseStudy, bool $includeBody = false): array
    {
        $chapters = collect($caseStudy->content_blocks ?? [])
            ->map(function (array $chapter): array {
                return [
                    'num' => $chapter['num'] ?? null,
                    'title' => $chapter['title'] ?? null,
                    'paragraphs' => array_values($chapter['paragraphs'] ?? []),
                    'images' => collect($chapter['images'] ?? [])
                        ->map(function (array $group): array {
                            return [
                                'layout' => $group['layout'] ?? 'full',
                                'items' => collect($group['items'] ?? [])
                                    ->map(function (array $item): ?array {
                                        $asset = ! empty($item['media_asset_id']) ? MediaAsset::find($item['media_asset_id']) : null;
                                        if (! $asset) {
                                            return null;
                                        }

                                        return [
                                            'src' => $asset->url,
                                            'alt' => $asset->alt_text ?: $asset->title,
                                            'title' => $asset->title,
                                            'caption' => $item['caption'] ?? null,
                                        ];
                                    })
                                    ->filter()
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return [
            'title' => $caseStudy->title,
            'slug' => $caseStudy->slug,
            'client_name' => $caseStudy->client_name,
            'industry' => $caseStudy->industry,
            'summary' => $caseStudy->summary,
            'body' => $includeBody ? $caseStudy->body : null,
            'cover_image' => $caseStudy->mediaAsset ? $this->mediaPayload($caseStudy->mediaAsset) : null,
            'chapters' => $includeBody ? $chapters : [],
            'featured' => $caseStudy->featured,
            'published_at' => optional($caseStudy->published_at)?->toIso8601String(),
        ];
    }

    private function mediaPayload(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'alt_text' => $asset->alt_text,
            'mime_type' => $asset->mime_type,
            'url' => $asset->url,
        ];
    }

    private function articlePayload(Article $article, bool $includeBody = false): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'type' => $article->type,
            'excerpt' => $article->excerpt,
            'body' => $includeBody ? $article->body : null,
            'featured' => $article->featured,
            'published_at' => optional($article->published_at)?->toIso8601String(),
        ];
    }
}
