<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JournalMessageAttachment extends Model
{
    protected $fillable = ['journal_message_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(JournalMessage::class, 'journal_message_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function humanSize(): string
    {
        if ($this->size < 1024) {
            return "{$this->size} B";
        }

        if ($this->size < 1024 * 1024) {
            return round($this->size / 1024, 1).' KB';
        }

        return round($this->size / (1024 * 1024), 1).' MB';
    }

    /**
     * Inline payload for providers that accept multimodal parts (images,
     * PDFs, plain text) — kept small on purpose, see upload size limits.
     *
     * @return array{mime_type: string, data: string}
     */
    public function toAiPayload(): array
    {
        // Gemini's inline_data only officially recognizes a handful of mime
        // types — images and PDF pass through as-is, everything else in our
        // upload whitelist (txt/md/csv/json) is plain text under the hood.
        $passthrough = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $mimeType = in_array($this->mime_type, $passthrough, true) ? $this->mime_type : 'text/plain';

        return [
            'mime_type' => $mimeType,
            'data' => base64_encode(Storage::disk($this->disk)->get($this->path) ?? ''),
        ];
    }
}
