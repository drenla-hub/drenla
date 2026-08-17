<?php

namespace App\AiAssistant\Data;

class AssistantRequest
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, AssistantMessage>  $history
     * @param  array<int, array{mime_type: string, data: string}>  $attachments
     */
    public function __construct(
        public readonly string $assistant,
        public readonly string $prompt,
        public readonly array $context = [],
        public readonly array $history = [],
        public readonly array $attachments = [],
        public readonly int $maxTurns = 4,
    ) {}
}
