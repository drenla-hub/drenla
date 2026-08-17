<?php

namespace App\AiAssistant\Data;

class ProviderResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<int, array<string, mixed>>  $actions
     */
    public function __construct(
        public readonly ?string $message = null,
        public readonly array $toolCalls = [],
        public readonly array $actions = [],
        public readonly string $action = 'chat',
        public readonly bool $completed = true,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $toolCalls = array_map(
            static fn (array $call): ToolCall => ToolCall::fromArray($call),
            array_values(array_filter($payload['tool_calls'] ?? [], 'is_array'))
        );

        return new self(
            message: isset($payload['message']) ? (string) $payload['message'] : null,
            toolCalls: $toolCalls,
            actions: array_values(array_filter($payload['actions'] ?? [], 'is_array')),
            action: (string) ($payload['action'] ?? 'chat'),
            completed: (bool) ($payload['completed'] ?? true),
        );
    }
}
