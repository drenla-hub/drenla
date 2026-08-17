<?php

use App\Models\HomepageSection;
use App\Models\User;

it('redirects guests away from homepage section management', function () {
    $this->get('/admin/homepage')->assertRedirect('/admin/login');
});

it('allows admins to list homepage sections', function () {
    $admin = User::factory()->superAdmin()->create();

    HomepageSection::query()->create([
        'key' => 'hero',
        'title' => 'Drenla builds premium systems.',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $this->actingAs($admin)
        ->get('/admin/homepage')
        ->assertOk()
        ->assertSee('Drenla builds premium systems.');
});

it('creates a homepage section with structured highlights', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post('/admin/homepage', [
            'key' => 'capabilities',
            'title' => 'What we do',
            'eyebrow' => 'Capabilities',
            'body' => 'Brand, place, and delivery systems.',
            'highlights_text' => "Brand launch systems\nBuilt environment positioning\n\n",
            'sort_order' => 2,
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.homepage.index'));

    $section = HomepageSection::where('key', 'capabilities')->firstOrFail();

    expect($section->title)->toBe('What we do');
    expect($section->is_published)->toBeTrue();
    expect($section->payload['highlights'])->toBe([
        'Brand launch systems',
        'Built environment positioning',
    ]);
});

it('requires a unique key when creating a homepage section', function () {
    $admin = User::factory()->superAdmin()->create();

    HomepageSection::query()->create([
        'key' => 'hero',
        'title' => 'Existing hero',
    ]);

    $this->actingAs($admin)
        ->post('/admin/homepage', [
            'key' => 'hero',
            'title' => 'Duplicate hero',
        ])
        ->assertSessionHasErrors('key');
});

it('updates a homepage section', function () {
    $admin = User::factory()->superAdmin()->create();

    $section = HomepageSection::query()->create([
        'key' => 'hero',
        'title' => 'Original title',
        'is_published' => true,
    ]);

    $this->actingAs($admin)
        ->put("/admin/homepage/{$section->id}", [
            'key' => 'hero',
            'title' => 'Updated title',
            'is_published' => '0',
        ])
        ->assertRedirect(route('admin.homepage.index'));

    expect($section->fresh()->title)->toBe('Updated title');
    expect($section->fresh()->is_published)->toBeFalse();
});

it('deletes a homepage section', function () {
    $admin = User::factory()->superAdmin()->create();

    $section = HomepageSection::query()->create([
        'key' => 'hero',
        'title' => 'To delete',
    ]);

    $this->actingAs($admin)
        ->delete("/admin/homepage/{$section->id}")
        ->assertRedirect(route('admin.homepage.index'));

    expect(HomepageSection::find($section->id))->toBeNull();
});

it('prevents non admin users from managing homepage sections', function () {
    $user = User::factory()->create(['role' => 'client']);

    $this->actingAs($user)
        ->get('/admin/homepage')
        ->assertForbidden();
});
