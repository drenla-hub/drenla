<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaAssetController extends Controller
{
    use HandlesMediaUploads;

    public function index(): View
    {
        $mediaAssets = MediaAsset::with('uploader')
            ->orderByDesc('id')
            ->get();

        return view('admin.media.index', compact('mediaAssets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $this->storeOrUpdateMediaAsset(
            uploadedFile: $request->file('file'),
            existingId: null,
            title: $validated['title'] ?? null,
            altText: $validated['alt_text'] ?? null,
            folder: 'media',
        );

        return redirect()->route('admin.media.index')->with('status', 'Media asset uploaded.');
    }

    public function destroy(MediaAsset $media): RedirectResponse
    {
        if ($media->disk && $media->path && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media asset deleted.');
    }
}
