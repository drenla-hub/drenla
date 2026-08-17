<?php

namespace App\AiAssistant\Data;

class ConversationState
{
    /**
     * @param  array<int, AssistantMessage>  $messages
     * @param  array<int, array<string, mixed>>  $toolResults
     */
    public function __construct(
        public readonly string $systemPrompt,
        public array $messages = [],
        public array $toolResults = [],
    ) {}

    /**
     * @param  array<int, AssistantMessage>  $history
     * @param  array<int, array{mime_type: string, data: string}>  $attachments
     */
    public static function start(string $systemPrompt, array $history, string $prompt, array $attachments = []): self
    {
        $messages = array_values($history);
        $messages[] = new AssistantMessage('user', $prompt, attachments: $attachments);

        return new self($systemPrompt, $messages);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function recordToolResult(string $tool, array $arguments, array $result): void
    {
        $entry = [
            'tool' => $tool,
            'arguments' => $arguments,
            'result' => $result,
        ];

        $this->toolResults[] = $entry;
        $this->messages[] = new AssistantMessage('tool', json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}', [
            'tool' => $tool,
        ]);
    }
}
