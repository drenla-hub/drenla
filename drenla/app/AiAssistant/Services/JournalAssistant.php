<?php

namespace App\AiAssistant\Services;

use App\AiAssistant\Data\AssistantMessage;
use App\AiAssistant\Data\AssistantRequest;
use App\AiAssistant\Data\AssistantResult;

class JournalAssistant
{
    public function __construct(
        protected AiAssistantManager $manager,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<int, array{mime_type: string, data: string}>  $attachments
     */
    public function respond(string $prompt, array $context = [], array $history = [], array $attachments = []): AssistantResult
    {
        return $this->manager->respond(new AssistantRequest(
            assistant: 'journal',
            prompt: $prompt,
            context: $context,
            history: array_map(
                static fn (array $message): AssistantMessage => AssistantMessage::fromArray($message),
                array_values(array_filter($history, 'is_array'))
            ),
            attachments: $attachments,
        ));
    }
}
