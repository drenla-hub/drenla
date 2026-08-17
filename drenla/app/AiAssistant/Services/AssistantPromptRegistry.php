<?php

namespace App\AiAssistant\Services;

use App\AiAssistant\Contracts\AssistantPrompt;
use InvalidArgumentException;

class AssistantPromptRegistry
{
    /**
     * @param  array<string, AssistantPrompt>  $prompts  keyed by prompt identifier (config('ai_assistant.assistants.*.prompt'))
     */
    public function __construct(
        protected array $prompts = [],
    ) {}

    public function get(string $key): AssistantPrompt
    {
        if (! isset($this->prompts[$key])) {
            throw new InvalidArgumentException("Unknown assistant prompt [{$key}].");
        }

        return $this->prompts[$key];
    }
}
