<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;

trait HandlesMediaUploads
{
    protected function storeOrUpdateMediaAsset(
        ?UploadedFile $uploadedFile,
        ?int $existingId,
        ?string $title,
        ?string $altText,
        string $folder = 'media',
    ): ?MediaAsset {
        $title = trim((string) $title);
        $altText = trim((string) $altText);

        if ($uploadedFile instanceof UploadedFile) {
            $path = $uploadedFile->store($folder, 'public');

            return MediaAsset::create([
                'title' => $title !== '' ? $title : $uploadedFile->getClientOriginalName(),
                'disk' => 'public',
                'path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'alt_text' => $altText !== '' ? $altText : null,
                'size' => $uploadedFile->getSize(),
                'uploaded_by' => $this->nullableInt(auth()->id()),
            ]);
        }

        if ($existingId === null) {
            return null;
        }

        $asset = MediaAsset::find($existingId);

        if ($asset === null) {
            return null;
        }

        if ($title !== '' || $altText !== '') {
            $asset->fill([
                'title' => $title !== '' ? $title : $asset->title,
                'alt_text' => $altText !== '' ? $altText : $asset->alt_text,
            ])->save();
        }

        return $asset;
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
