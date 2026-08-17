<?php

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('redirects guests away from the media library', function () {
    $this->get('/admin/media')->assertRedirect('/admin/login');
});

it('allows admins to list media assets', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    MediaAsset::query()->create([
        'title' => 'Existing asset',
        'disk' => 'public',
        'path' => 'media/existing.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    $this->actingAs($admin)
        ->get('/admin/media')
        ->assertOk()
        ->assertSee('Existing asset');
});

it('uploads a media asset to the public disk', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post('/admin/media', [
            'file' => UploadedFile::fake()->image('cover.jpg'),
            'title' => 'Cover shot',
            'alt_text' => 'A cover shot',
        ])
        ->assertRedirect(route('admin.media.index'));

    $asset = MediaAsset::where('title', 'Cover shot')->firstOrFail();

    expect($asset->disk)->toBe('public');
    expect($asset->alt_text)->toBe('A cover shot');
    expect($asset->uploaded_by)->toBe($admin->id);
    Storage::disk('public')->assertExists($asset->path);
});

it('requires an image file to upload a media asset', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post('/admin/media', [
            'title' => 'No file',
        ])
        ->assertSessionHasErrors('file');
});

it('deletes a media asset and its stored file', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $path = UploadedFile::fake()->image('to-delete.jpg')->store('media', 'public');

    $asset = MediaAsset::query()->create([
        'title' => 'To delete',
        'disk' => 'public',
        'path' => $path,
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    $this->actingAs($admin)
        ->delete("/admin/media/{$asset->id}")
        ->assertRedirect(route('admin.media.index'));

    expect(MediaAsset::find($asset->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('prevents non admin users from managing media assets', function () {
    $user = User::factory()->create(['role' => 'client']);

    $this->actingAs($user)
        ->get('/admin/media')
        ->assertForbidden();
});
