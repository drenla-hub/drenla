<?php

namespace App\AiAssistant\Data;

class AssistantResult
{
    /**
     * @param  array<int, array<string, mixed>>  $toolResults
     * @param  array<int, array<string, mixed>>  $actions
     */
    public function __construct(
        public readonly string $assistant,
        public readonly string $message,
        public readonly array $toolResults = [],
        public readonly array $actions = [],
        public readonly string $action = 'chat',
        public readonly string $provider = 'null',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'assistant' => $this->assistant,
            'message' => $this->message,
            'tool_results' => $this->toolResults,
            'actions' => $this->actions,
            'action' => $this->action,
            'provider' => $this->provider,
        ];
    }
}
