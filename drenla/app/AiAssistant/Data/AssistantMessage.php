<?php

namespace App\AiAssistant\Data;

class AssistantMessage
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<int, array{mime_type: string, data: string}>  $attachments  Base64-encoded file data, sent to providers that support inline multimodal parts (images, PDFs).
     */
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly array $meta = [],
        public readonly array $attachments = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            role: (string) ($payload['role'] ?? 'user'),
            content: (string) ($payload['content'] ?? $payload['text'] ?? ''),
            meta: is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            attachments: is_array($payload['attachments'] ?? null) ? $payload['attachments'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'meta' => $this->meta,
            'attachments' => $this->attachments,
        ];
    }
}
