<?php

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\FocusArea;
use App\Models\HomepageSection;
use App\Models\SiteSetting;

it('returns public site settings and homepage sections', function () {
    SiteSetting::putValue('site_name', 'Drenla');
    HomepageSection::create([
        'key' => 'hero',
        'title' => 'Hero',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $this->getJson('/api/site/settings')
        ->assertOk()
        ->assertJsonPath('site_name', 'Drenla');

    $this->getJson('/api/site/home')
        ->assertOk()
        ->assertJsonCount(1, 'sections');
});

it('returns only published work insights resources and focus areas', function () {
    CaseStudy::create(['title' => 'Visible Work', 'slug' => 'visible-work', 'status' => 'published', 'published_at' => now()]);
    CaseStudy::create(['title' => 'Hidden Work', 'slug' => 'hidden-work', 'status' => 'draft']);

    Article::create(['title' => 'Visible Insight', 'slug' => 'visible-insight', 'type' => 'insight', 'status' => 'published', 'published_at' => now()]);
    Article::create(['title' => 'Hidden Insight', 'slug' => 'hidden-insight', 'type' => 'insight', 'status' => 'draft']);
    Article::create(['title' => 'Visible Resource', 'slug' => 'visible-resource', 'type' => 'resource', 'status' => 'published', 'published_at' => now()]);

    FocusArea::create(['title' => 'Visible Focus', 'slug' => 'visible-focus', 'status' => 'published', 'published_at' => now()]);
    FocusArea::create(['title' => 'Hidden Focus', 'slug' => 'hidden-focus', 'status' => 'draft']);

    $this->getJson('/api/site/work')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/site/insights')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/site/resources')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/site/focus-areas')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
